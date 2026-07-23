<?php

namespace App\Services\Operaciones;

use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\Operacion;
use App\Models\Transaccion;
use App\Models\TipoOperacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreOperacionService
{
    const TOLERANCIA_USD = 0.01;

    /**
     * Valida que las transacciones confirmadas estén balanceadas
     * respecto al monto_solicitado y la tasa de la operación.
     *
     * Regla:
     * - Suma de transacciones en la moneda de operación (divisa) = monto_solicitado
     * - Suma de transacciones en VES = monto_solicitado × tasa_aplicada
     *
     * @throws ValidationException
     */
    public function validarBalance(Operacion $operacion, Collection $transacciones): void
    {
        $monedaOperacion = $operacion->monedaOperacion;

        if (!$monedaOperacion) {
            return;
        }

        $tasa = (float) $operacion->tasa_aplicada;
        $montoSolicitado = (float) $operacion->monto_solicitado;
        $totalDivisa = 0;
        $totalVes = 0;

        foreach ($transacciones as $t) {
            if ($t->estado !== 'confirmada') {
                continue;
            }
            if ($t->moneda_id === $monedaOperacion->id) {
                $totalDivisa += (float) $t->monto;
            } elseif ($t->moneda?->codigo === 'VES') {
                $totalVes += (float) $t->monto;
            }
        }

        $expectedVes = round($montoSolicitado * $tasa, 2);
        $diffDivisa = abs($totalDivisa - $montoSolicitado);
        $diffVes = abs($totalVes - $expectedVes);

        $errores = [];
        if ($diffDivisa > self::TOLERANCIA_USD) {
            $codigo = $monedaOperacion->codigo ?? 'Divisa';
            $errores[] = "Total en {$codigo}: {$totalDivisa}, esperado: {$montoSolicitado} (diferencia: {$diffDivisa}).";
        }
        if ($diffVes > self::TOLERANCIA_USD) {
            $errores[] = "Total en VES: {$totalVes}, esperado: {$expectedVes} (diferencia: {$diffVes}).";
        }

        if (!empty($errores)) {
            throw ValidationException::withMessages([
                'transacciones' => 'Las transacciones confirmadas no están balanceadas: ' . implode(' ', $errores),
            ]);
        }
    }

    /**
     * Valida que ninguna cuenta de la casa (con titular_id) quede con saldo negativo
     * después de aplicar las transacciones. Si config('fifo.permitir_sobregiro') es true, omite.
     *
     * @throws ValidationException
     */
    public function validarSaldosSuficientes(Collection $transacciones): void
    {
        if (config('fifo.permitir_sobregiro', false)) {
            return;
        }

        $saldos = [];

        foreach ($transacciones as $t) {
            if ($t->estado !== 'confirmada') {
                continue;
            }

            $cuentaOrigen = Cuenta::find($t->cuenta_origen_id);
            if ($cuentaOrigen && $cuentaOrigen->titular_id) {
                $key = $cuentaOrigen->id;
                $saldos[$key] = ($saldos[$key] ?? (float) $cuentaOrigen->saldo_cache) - (float) $t->monto;
            }

            $cuentaDestino = Cuenta::find($t->cuenta_destino_id);
            if ($cuentaDestino && $cuentaDestino->titular_id) {
                $key = $cuentaDestino->id;
                $saldos[$key] = ($saldos[$key] ?? (float) $cuentaDestino->saldo_cache) + (float) $t->monto;
            }
        }

        $errores = [];
        foreach ($saldos as $cuentaId => $saldoFinal) {
            if ($saldoFinal < 0) {
                $cuenta = Cuenta::find($cuentaId);
                $alias = $cuenta?->alias ?? "ID #{$cuentaId}";
                $errores[] = "La cuenta {$alias} quedaría con saldo negativo ({$saldoFinal}).";
            }
        }

        if (!empty($errores)) {
            throw ValidationException::withMessages([
                'saldos' => 'Saldo insuficiente en cuentas de la casa: ' . implode(' ', $errores),
            ]);
        }
    }

    /**
     * Genera movimientos contables desde las transacciones confirmadas.
     * Cada transacción genera un movimiento de salida (-) y otro de entrada (+).
     *
     * @return Collection<int, Movimiento>
     */
    public function generarMovimientos(Operacion $operacion, Collection $transacciones): Collection
    {
        $movimientos = collect();

        foreach ($transacciones as $i => $t) {
            $esFiat = in_array($t->moneda->codigo ?? '', ['USD', 'USDT']);
            $tasaUsd = $esFiat ? 1.0 : ($t->tasa_aplicada ? round(1 / $t->tasa_aplicada, 8) : 1.0);
            $baseOrden = $operacion->movimientos()->max('orden') ?? 0;

            if ($t->cuenta_origen_id) {
                $movimientos->push($operacion->movimientos()->create([
                    'cuenta_id'             => $t->cuenta_origen_id,
                    'moneda_id'             => $t->moneda_id,
                    'monto'                 => -$t->monto,
                    'tasa_a_usd'            => $tasaUsd,
                    'monto_usd_equivalente' => round($t->monto * $tasaUsd, 2),
                    'orden'                 => $baseOrden + ($i * 2) + 1,
                ]));
            }

            if ($t->cuenta_destino_id) {
                $movimientos->push($operacion->movimientos()->create([
                    'cuenta_id'             => $t->cuenta_destino_id,
                    'moneda_id'             => $t->moneda_id,
                    'monto'                 => $t->monto,
                    'tasa_a_usd'            => $tasaUsd,
                    'monto_usd_equivalente' => round($t->monto * $tasaUsd, 2),
                    'orden'                 => $baseOrden + ($i * 2) + 2,
                ]));
            }
        }

        return $movimientos;
    }

    /**
     * Calcula la ganancia bruta de la operación en USD y VES.
     * Es un snapshot congelado al momento de la operación; no se recalcula
     * aunque cambien las tasas de mercado.
     *
     * @return array{usd: float, ves: float}
     */
    public function calcularGanancia(Operacion $operacion): array
    {
        $tipo = $operacion->tipoOperacion;

        if (!$tipo->genera_ganancia) {
            return ['usd' => 0.0, 'ves' => 0.0];
        }

        $codigo = $tipo->codigo;
        $codigoDivisa = $operacion->monedaOperacion?->codigo ?? 'USD';

        switch ($codigo) {
            case 'venta_usd':
                if (is_null($operacion->tasa_mercado_snapshot) || is_null($operacion->tasa_aplicada)) {
                    return ['usd' => 0.0, 'ves' => 0.0];
                }

                $montoDivisa = $operacion->movimientos
                    ->filter(fn ($m) => (float) $m->monto < 0 && $m->moneda->codigo === $codigoDivisa)
                    ->sum(fn ($m) => abs((float) $m->monto));

                $gananciaVes = $montoDivisa * ((float) $operacion->tasa_aplicada - (float) $operacion->tasa_mercado_snapshot);
                $gananciaUsd = $gananciaVes / (float) $operacion->tasa_aplicada;

                return ['usd' => round($gananciaUsd, 2), 'ves' => round($gananciaVes, 2)];

            case 'compra_usd':
                if (is_null($operacion->tasa_mercado_snapshot) || is_null($operacion->tasa_aplicada)) {
                    return ['usd' => 0.0, 'ves' => 0.0];
                }

                $montoDivisa = $operacion->movimientos
                    ->filter(fn ($m) => (float) $m->monto > 0 && $m->moneda->codigo === $codigoDivisa)
                    ->sum(fn ($m) => (float) $m->monto);

                $gananciaVes = $montoDivisa * ((float) $operacion->tasa_mercado_snapshot - (float) $operacion->tasa_aplicada);
                $gananciaUsd = $gananciaVes / (float) $operacion->tasa_mercado_snapshot;

                return ['usd' => round($gananciaUsd, 2), 'ves' => round($gananciaVes, 2)];

            case 'comision':
                $movIngreso = $operacion->movimientos->first(fn ($m) => (float) $m->monto > 0);

                if (!$movIngreso) {
                    return ['usd' => 0.0, 'ves' => 0.0];
                }

                $gananciaUsd = (float) $movIngreso->monto_usd_equivalente;

                if ($movIngreso->moneda->codigo === 'VES') {
                    $gananciaVes = (float) $movIngreso->monto;
                } else {
                    $gananciaVes = !is_null($operacion->tasa_mercado_snapshot)
                        ? round($gananciaUsd * (float) $operacion->tasa_mercado_snapshot, 2)
                        : 0.0;
                }

                return ['usd' => round($gananciaUsd, 2), 'ves' => round($gananciaVes, 2)];

            default:
                return ['usd' => 0.0, 'ves' => 0.0];
        }
    }

    /**
     * Valida comprobante obligatorio para transacciones con método de pago no efectivo.
     *
     * @throws ValidationException
     */
    public function validarComprobantes(Collection $transacciones): void
    {
        foreach ($transacciones as $t) {
            if (($t->metodo_pago ?? '') !== 'efectivo' && empty($t->comprobante)) {
                throw ValidationException::withMessages([
                    'comprobante' => "La transacción #{$t->orden} no tiene comprobante adjunto (requerido para método de pago: {$t->metodo_pago}).",
                ]);
            }
        }
    }

    /**
     * Obtiene los IDs de cuentas afectadas por una colección de transacciones.
     */
    public function cuentasAfectadas(Collection $transacciones): array
    {
        $ids = [];
        foreach ($transacciones as $t) {
            if ($t->cuenta_origen_id) $ids[] = $t->cuenta_origen_id;
            if ($t->cuenta_destino_id) $ids[] = $t->cuenta_destino_id;
        }
        return array_unique($ids);
    }
}
