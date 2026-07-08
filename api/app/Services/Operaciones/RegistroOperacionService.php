<?php

namespace App\Services\Operaciones;

use App\Jobs\ProcesarFifoOperacionJob;
use App\Jobs\RecalcularSaldoCuentaJob;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Services\Configuracion\CalculadorComisionesService;
use App\Services\Configuracion\TasaDiariaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// src/Services/Operaciones/RegistroOperacionService.php
class RegistroOperacionService
{
    /**
     * Tolerancia en USD para la invariante de partida doble.
     * Cubre imprecisión de tasas decimales (e.g. 1/36.50 no es exacto).
     */
    const TOLERANCIA_USD = 0.01;

    public function __construct(
        private readonly TasaDiariaService $tasaService,
        private readonly CalculadorComisionesService $comisionesService,
    ) {}

    /**
     * Registra una operación de negocio con sus movimientos contables.
     *
     * Flujo:
     * 1. Identifica tipo de operación.
     * 2. Valida movimientos (cuadre, cuentas activas, etc.).
     * 3. Resuelve tasa sugerida del día y valida la tasa efectiva del operador.
     * 4. Crea operación + movimientos en transacción.
     * 5. Calcula ganancia bruta.
     * 6. Aplica comisiones automáticamente.
     * 7. Despacha jobs de recálculo de saldos y FIFO.
     *
     * @param array $payload Ver estructura en docblock de validarMovimientos.
     */
    public function registrar(array $payload): Operacion
    {
        $tipo = TipoOperacion::where('codigo', $payload['tipo_codigo'])->firstOrFail();

        // Si es intermediada, no se usa tasa diaria
        if ($tipo->codigo === 'intermediada') {
            return $this->registrarIntermediada($payload, $tipo);
        }

        // Auto-calcular tasa_a_usd si no viene en el payload
        $tasaAplicada = (float) ($payload['tasa_aplicada'] ?? 1);
        $payload['movimientos'] = array_map(function ($mov) use ($tasaAplicada) {
            if (!empty($mov['tasa_a_usd'])) {
                return $mov;
            }
            $cuenta = Cuenta::with('moneda')->find($mov['cuenta_id']);
            $codigo = $cuenta?->moneda?->codigo;
            $mov['tasa_a_usd'] = match ($codigo) {
                'USD', 'USDT' => 1.0,
                default       => $tasaAplicada > 0 ? round(1 / $tasaAplicada, 8) : 1.0,
            };
            return $mov;
        }, $payload['movimientos']);

        $this->validarMovimientos($payload['movimientos'], $tipo);

        // ── Resolver tasa diaria y validar tasa efectiva ──────────────────────
        [$tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia] =
            $this->resolverTasa($payload, $tipo);

        return DB::transaction(function () use ($payload, $tipo, $tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia) {
            $operacion = Operacion::create([
                'fecha'                  => $payload['fecha'],
                'tipo_operacion_id'      => $tipo->id,
                'cliente_id'             => $payload['cliente_id'] ?? null,
                'categoria_gasto_id'     => $payload['categoria_gasto_id'] ?? null,
                'operador_id'            => $payload['operador_id'],
                'tasa_aplicada'          => $tasaEfectiva,
                'genera_comision'        => $payload['genera_comision'] ?? false,
                'monto_comision'         => $payload['monto_comision'] ?? 0,
                'tipo_comision'          => $payload['tipo_comision'] ?? null,
                'tasa_sugerida'          => $tasaSugerida,
                'tasa_diaria_id'         => $tasaDiaria?->id,
                'sin_tasa_referencia'    => $sinTasaReferencia,
                'tasa_mercado_snapshot'  => $payload['tasa_mercado_snapshot'] ?? null,
                'fuente_tasa_mercado'    => $payload['fuente_tasa_mercado'] ?? null,
                'ganancia_bruta_usd'     => 0,
                'ganancia_bruta_ves'     => 0,
                'referencia'             => $payload['referencia'] ?? null,
                'descripcion'            => $payload['descripcion'] ?? null,
                'estatus'                => 'sin_verificar',
                'estado_pool'            => 'pendiente',
                'origen'                 => $payload['origen'] ?? 'manual',
                'origen_referencia'      => $payload['origen_referencia'] ?? null,
            ]);

            foreach ($payload['movimientos'] as $index => $movData) {
                // moneda_id viene de la cuenta, NO del payload, para garantizar coherencia.
                $cuenta = Cuenta::findOrFail($movData['cuenta_id']);

                $operacion->movimientos()->create([
                    'cuenta_id'             => $cuenta->id,
                    'moneda_id'             => $cuenta->moneda_id,
                    'monto'                 => $movData['monto'],
                    'tasa_a_usd'            => $movData['tasa_a_usd'],
                    'monto_usd_equivalente' => round($movData['monto'] * $movData['tasa_a_usd'], 4),
                    'orden'                 => $index + 1,
                ]);
            }

            if ($tipo->genera_ganancia) {
                $operacion->setRelation('tipoOperacion', $tipo);
                $operacion->load('movimientos.moneda');

                $ganancia = $this->calcularGananciaBruta($operacion);

                $operacion->update([
                    'ganancia_bruta_usd' => $ganancia['usd'],
                    'ganancia_bruta_ves' => $ganancia['ves'],
                ]);
            }

            // Aplicar comisiones automáticamente (idempotente, incluye recálculo de netas)
            $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
            $this->comisionesService->aplicarAOperacion($operacion);

            // Actualizar saldo de cuentas afectadas automáticamente
            foreach ($payload['movimientos'] as $movData) {
                $cuenta = Cuenta::find($movData['cuenta_id']);
                if ($cuenta && $cuenta->saldo_cache_at) {
                    $nuevoSaldo = bcadd($cuenta->saldo_cache, $movData['monto'], 4);
                    $cuenta->update(['saldo_cache' => $nuevoSaldo]);
                }
            }

            if ($tipo->afecta_fifo) {
                ProcesarFifoOperacionJob::dispatch($operacion->id);
            }

            return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion']);
        });
    }

    /**
     * Resuelve tasa sugerida, efectiva, diaria_id y flag sin_tasa_referencia.
     *
     * Reglas:
     * - Para tipos con dirección (venta/compra): busca tasa diaria vigente.
     *   Si no existe, usa la última publicada y marca sin_tasa_referencia=true.
     *   Si ninguna existe, lanza ValidationException.
     * - Valida que la tasa efectiva enviada por el operador no sea desfavorable a la casa.
     *
     * @return array{0: ?TasaDiaria, 1: ?float, 2: ?float, 3: bool}
     */
    private function resolverTasa(array $payload, TipoOperacion $tipo): array
    {
        $direccion = $this->tasaService->direccionDeTipo($tipo->codigo);

        // Sin dirección → no aplica tasa diaria
        if ($direccion === null) {
            $tasaEfectiva = $payload['tasa_aplicada'] ?? null;
            return [null, null, $tasaEfectiva, false];
        }

        // Enriquecer movimientos con moneda_id desde la cuenta para identificarPar
        $movsConMoneda = array_map(function ($mov) {
            $cuenta = Cuenta::find($mov['cuenta_id']);
            return array_merge($mov, ['moneda_id' => $cuenta?->moneda_id]);
        }, $payload['movimientos']);

        $par = $this->tasaService->identificarPar($movsConMoneda);

        $sinTasaReferencia = false;
        $tasaDiaria = $this->tasaService->obtenerVigente(
            $par['moneda_base_id'],
            $par['moneda_cotizada_id'],
        );

        if ($tasaDiaria === null) {
            $tasaDiaria = $this->tasaService->obtenerUltimaPublicada(
                $par['moneda_base_id'],
                $par['moneda_cotizada_id'],
            );

            if ($tasaDiaria === null) {
                $base      = Moneda::find($par['moneda_base_id'])?->codigo ?? "ID:{$par['moneda_base_id']}";
                $cotizada  = Moneda::find($par['moneda_cotizada_id'])?->codigo ?? "ID:{$par['moneda_cotizada_id']}";
                throw ValidationException::withMessages([
                    'tasa' => "No existe tasa configurada para el par {$base}/{$cotizada}. Solicite al admin que la publique.",
                ]);
            }

            $sinTasaReferencia = true;
        }

        $tasaSugerida = $direccion === 'venta'
            ? (float) $tasaDiaria->tasa_venta
            : (float) $tasaDiaria->tasa_compra;

        $tasaEfectiva = isset($payload['tasa_aplicada'])
            ? (float) $payload['tasa_aplicada']
            : $tasaSugerida;

        // Tasa desfavorable respecto al mínimo configurado: requiere justificación.
        // La justificación se toma del campo descripcion (las operaciones no tienen campo notas dedicado).
        if ($tasaDiaria->esDesfavorableParaLaCasa($tasaEfectiva, $direccion)) {
            $justificacion = trim((string) ($payload['descripcion'] ?? ''));
            if ($justificacion === '') {
                throw ValidationException::withMessages([
                    'tasa_aplicada' => 'Esta tasa es desfavorable para la casa. Debe agregar una justificación en el campo notas.',
                ]);
            }
        }

        return [$tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia];
    }

    /**
     * Valida los movimientos según las reglas del tipo de operación.
     *
     * Reglas por tipo:
     * - ajuste_apertura : exactamente 1 movimiento (saldo inicial).
     * - gasto / comision / ajuste : mínimo 1, sin invariante de cuadre.
     * - venta_usd / compra_usd / cambio / traslado : mínimo 2 y Σ(monto×tasa_a_usd) ≈ 0.
     *
     * Siempre: ninguna cuenta_id puede corresponder a una cuenta inactiva.
     */
    private function validarMovimientos(array $movs, TipoOperacion $tipo): void
    {
        $cuentaIds = array_column($movs, 'cuenta_id');
        $inactivas = Cuenta::whereIn('id', $cuentaIds)->where('activa', false)->get();

        if ($inactivas->isNotEmpty()) {
            $aliases = $inactivas->pluck('alias')->join(', ');
            throw ValidationException::withMessages([
                'movimientos' => "Las siguientes cuentas están inactivas: {$aliases}.",
            ]);
        }

        if ($tipo->codigo === 'ajuste_apertura') {
            if (count($movs) !== 1) {
                throw ValidationException::withMessages([
                    'movimientos' => 'El tipo ajuste_apertura permite exactamente 1 movimiento.',
                ]);
            }
            return;
        }

        if (in_array($tipo->codigo, ['gasto', 'comision', 'ajuste'])) {
            return;
        }

        if (in_array($tipo->codigo, ['venta_usd', 'compra_usd', 'cambio', 'traslado'])) {
            if (count($movs) < 2) {
                throw ValidationException::withMessages([
                    'movimientos' => "El tipo {$tipo->codigo} requiere mínimo 2 movimientos.",
                ]);
            }

            $sumaUsd = array_sum(
                array_map(fn($m) => (float) $m['monto'] * (float) $m['tasa_a_usd'], $movs)
            );

            if (abs($sumaUsd) > self::TOLERANCIA_USD) {
                throw ValidationException::withMessages([
                    'movimientos' => sprintf(
                        'Los movimientos no cuadran en partida doble. Diferencia: %s USD (tolerancia permitida: %s USD).',
                        number_format($sumaUsd, 6),
                        self::TOLERANCIA_USD
                    ),
                ]);
            }
        }
    }

    /**
     * Calcula la ganancia bruta de la operación en USD y VES.
     *
     * La ganancia bruta se modela como spread entre tasa aplicada y tasa de mercado,
     * NO como movimiento extra (lo que rompería la invariante Σ=0).
     * Es un snapshot congelado al momento de la operación; no se recalcula aunque
     * cambien las tasas de mercado.
     *
     * @return array{usd: float, ves: float}
     */
    private function calcularGananciaBruta(Operacion $operacion): array
    {
        $codigo = $operacion->tipoOperacion->codigo;

        switch ($codigo) {
            case 'venta_usd':
                /*
                 * La casa vende USD al cliente y recibe VES.
                 * El spread es la diferencia entre la tasa aplicada y la tasa de mercado (BCV/Binance).
                 *
                 * Fórmula:
                 *   ganancia_ves = monto_usd_vendido × (tasa_aplicada − tasa_mercado)
                 *   ganancia_usd = ganancia_ves / tasa_aplicada
                 *
                 * Ejemplo: 100 USD vendidos a 36.50, BCV en 36.42.
                 *   ganancia_ves = 100 × (36.50 − 36.42) = 8.00 Bs
                 *   ganancia_usd = 8.00 / 36.50 = 0.2192 USD
                 */
                if (is_null($operacion->tasa_mercado_snapshot) || is_null($operacion->tasa_aplicada)) {
                    return ['usd' => 0.0, 'ves' => 0.0];
                }

                $montoUsdVendido = $operacion->movimientos
                    ->filter(fn ($m) => (float) $m->monto < 0 && $m->moneda->codigo === 'USD')
                    ->sum(fn ($m) => abs((float) $m->monto));

                $gananciaVes = $montoUsdVendido * ((float) $operacion->tasa_aplicada - (float) $operacion->tasa_mercado_snapshot);
                $gananciaUsd = $gananciaVes / (float) $operacion->tasa_aplicada;

                return ['usd' => round($gananciaUsd, 4), 'ves' => round($gananciaVes, 2)];

            case 'compra_usd':
                /*
                 * La casa compra USD al cliente y entrega VES.
                 * Gana cuando compra por debajo de la tasa de mercado.
                 *
                 * Fórmula:
                 *   ganancia_ves = monto_usd_comprado × (tasa_mercado − tasa_aplicada)
                 *   ganancia_usd = ganancia_ves / tasa_mercado  ← se divide por tasa_mercado
                 *                                                  porque es la referencia "justa"
                 */
                if (is_null($operacion->tasa_mercado_snapshot) || is_null($operacion->tasa_aplicada)) {
                    return ['usd' => 0.0, 'ves' => 0.0];
                }

                $montoUsdComprado = $operacion->movimientos
                    ->filter(fn ($m) => (float) $m->monto > 0 && $m->moneda->codigo === 'USD')
                    ->sum(fn ($m) => (float) $m->monto);

                $gananciaVes = $montoUsdComprado * ((float) $operacion->tasa_mercado_snapshot - (float) $operacion->tasa_aplicada);
                $gananciaUsd = $gananciaVes / (float) $operacion->tasa_mercado_snapshot;

                return ['usd' => round($gananciaUsd, 4), 'ves' => round($gananciaVes, 2)];

            case 'comision':
                /*
                 * La comisión cobrada es ganancia neta directa.
                 * USD: monto_usd_equivalente del primer movimiento de ingreso.
                 * VES: si la comisión se cobró en VES, el monto ya es en Bs; de lo contrario,
                 *      se convierte usando tasa_mercado_snapshot (si no hay snapshot, queda en 0).
                 */
                $movIngreso = $operacion->movimientos->first(fn ($m) => (float) $m->monto > 0);

                if (! $movIngreso) {
                    return ['usd' => 0.0, 'ves' => 0.0];
                }

                $gananciaUsd = (float) $movIngreso->monto_usd_equivalente;

                if ($movIngreso->moneda->codigo === 'VES') {
                    $gananciaVes = (float) $movIngreso->monto;
                } else {
                    $gananciaVes = ! is_null($operacion->tasa_mercado_snapshot)
                        ? round($gananciaUsd * (float) $operacion->tasa_mercado_snapshot, 2)
                        : 0.0;
                }

                return ['usd' => round($gananciaUsd, 4), 'ves' => round((float) $gananciaVes, 2)];

            case 'cambio':
                /*
                 * TODO Fase 4: ganancia real en cambios multimoneda requiere tasa bilateral
                 * de referencia. Se aclara con el cliente y se implementa en FifoService.
                 */
                return ['usd' => 0.0, 'ves' => 0.0];

            default:
                // traslado, gasto, ajuste, ajuste_apertura: no generan ganancia directa.
                // Los gastos se reflejan en el movimiento mismo; P&L = Σganancias - Σgastos
                // se calcula en ReportesService (Fase 5).
                return ['usd' => 0.0, 'ves' => 0.0];
        }
    }

    /**
 * Actualiza una operación existente.
 *
 * Flujo:
 * 1. Valida que la operación sea editable (no verificada, no cancelada).
 * 2. Si cambian los movimientos, elimina los anteriores y crea los nuevos.
 * 3. Si cambia la tasa o los montos, recalcula ganancia bruta.
 * 4. Re-aplica comisiones automáticamente.
 * 5. Registra en bitácora quién editó y qué cambió.
 * 6. Despacha jobs de recálculo de saldos y FIFO.
 *
 * @param Operacion $operacion
 * @param array $payload
 * @param \App\Models\User $editor
 * @return Operacion
 */
public function actualizar(Operacion $operacion, array $payload, \App\Models\User $editor): Operacion
{
    // La validación de editable (no verificada, no cancelada) ya está en el FormRequest.

    return DB::transaction(function () use ($operacion, $payload, $editor) {
        $cambios = [];

        // ── 1. Actualizar campos básicos ────────────────────────────────
        $camposBasicos = [
            'fecha',
            'cliente_id',
            'categoria_gasto_id',
            'operador_id',
            'tasa_aplicada',
            'genera_comision',
            'monto_comision',
            'tipo_comision',
            'tasa_mercado_snapshot',
            'fuente_tasa_mercado',
            'referencia',
            'descripcion',
        ];

        foreach ($camposBasicos as $campo) {
            if (array_key_exists($campo, $payload)) {
                $valorAnterior = $operacion->$campo;
                $operacion->$campo = $payload[$campo];
                if ($valorAnterior != $payload[$campo]) {
                    $cambios[$campo] = ['anterior' => $valorAnterior, 'nuevo' => $payload[$campo]];
                }
            }
        }

        // ── 2. Si vienen movimientos nuevos, reemplazar ────────────────
        if (!empty($payload['movimientos'])) {
            $movimientosAnteriores = $operacion->movimientos->map(function ($m) {
                return [
                    'cuenta_id'  => $m->cuenta_id,
                    'monto'      => $m->monto,
                    'tasa_a_usd' => $m->tasa_a_usd,
                    'moneda_id'  => $m->moneda_id,
                ];
            })->toArray();

            // Auto-calcular tasa_a_usd si no viene en el payload
            $tasaAplicada = (float) ($payload['tasa_aplicada'] ?? $operacion->tasa_aplicada);
            $payload['movimientos'] = array_map(function ($mov) use ($tasaAplicada) {
                if (!empty($mov['tasa_a_usd'])) {
                    return $mov;
                }
                $cuenta = Cuenta::with('moneda')->find($mov['cuenta_id']);
                $codigo = $cuenta?->moneda?->codigo;
                $mov['tasa_a_usd'] = match ($codigo) {
                    'USD', 'USDT' => 1.0,
                    default       => $tasaAplicada > 0 ? round(1 / $tasaAplicada, 8) : 1.0,
                };
                return $mov;
            }, $payload['movimientos']);

            // Validar los nuevos movimientos
            $tipo = $operacion->tipoOperacion;
            $this->validarMovimientos($payload['movimientos'], $tipo);

            // Eliminar movimientos anteriores
            $operacion->movimientos()->delete();

            // Crear nuevos movimientos
            foreach ($payload['movimientos'] as $index => $movData) {
                $cuenta = Cuenta::findOrFail($movData['cuenta_id']);

                $operacion->movimientos()->create([
                    'cuenta_id'             => $cuenta->id,
                    'moneda_id'             => $cuenta->moneda_id,
                    'monto'                 => $movData['monto'],
                    'tasa_a_usd'            => $movData['tasa_a_usd'],
                    'monto_usd_equivalente' => round($movData['monto'] * $movData['tasa_a_usd'], 4),
                    'orden'                 => $index + 1,
                ]);
            }

            $cambios['movimientos'] = [
                'anterior' => $movimientosAnteriores,
                'nuevo'    => $payload['movimientos'],
            ];
        }

        // ── 3. Guardar cambios básicos ──────────────────────────────────
        $operacion->save();

        // ── 4. Recalcular ganancia bruta ────────────────────────────────
        $tipo = $operacion->tipoOperacion;
        if ($tipo->genera_ganancia) {
            $operacion->setRelation('tipoOperacion', $tipo);
            $operacion->load('movimientos.moneda');

            $ganancia = $this->calcularGananciaBruta($operacion);

            $operacion->update([
                'ganancia_bruta_usd' => $ganancia['usd'],
                'ganancia_bruta_ves' => $ganancia['ves'],
            ]);
        }

        // ── 5. Re-aplicar comisiones ────────────────────────────────────
        $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
        $this->comisionesService->aplicarAOperacion($operacion);

        // ── 6. Registrar en bitácora ─────────────────────────────────────
        if (!empty($cambios)) {
            activity()
                ->performedOn($operacion)
                ->causedBy($editor)
                ->withProperties([
                    'cambios'        => $cambios,
                    'motivo_edicion' => $payload['motivo_edicion'] ?? 'Sin motivo especificado',
                ])
                ->log('Operación editada');
        }

        // ── 7. Actualizar saldo de cuentas afectadas ───────────────────
        if (!empty($payload['movimientos'])) {
            foreach ($payload['movimientos'] as $movData) {
                $cuenta = Cuenta::find($movData['cuenta_id']);
                if ($cuenta && $cuenta->saldo_cache_at) {
                    $nuevoSaldo = bcadd($cuenta->saldo_cache, $movData['monto'], 4);
                    $cuenta->update(['saldo_cache' => $nuevoSaldo]);
                }
            }
        }

        if ($tipo->afecta_fifo) {
            ProcesarFifoOperacionJob::dispatch($operacion->id);
        }

        return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion']);
    });
}

    /**
     * Registra una operación intermediada (spread entre tasas de compra y venta).
     *
     * Flujo:
     * 1. Valida movimientos específicos de intermediada (mínimo 4).
     * 2. Crea operación con cliente_emisor_id y cliente_receptor_id.
     * 3. Crea movimientos sin validar cuadre estricto.
     * 4. Calcula ganancia como spread entre tasas.
     * 5. Aplica comisiones si corresponde.
     *
     * @param array $payload
     * @param TipoOperacion $tipo
     * @return Operacion
     */
    private function registrarIntermediada(array $payload, TipoOperacion $tipo): Operacion
    {
        $this->validarMovimientosIntermediada($payload['movimientos']);

        return DB::transaction(function () use ($payload, $tipo) {
            $operacion = Operacion::create([
                'fecha'                  => $payload['fecha'],
                'tipo_operacion_id'      => $tipo->id,
                'cliente_emisor_id'      => $payload['cliente_emisor_id'],
                'cliente_receptor_id'    => $payload['cliente_receptor_id'],
                'operador_id'            => $payload['operador_id'],
                'tasa_compra'            => $payload['tasa_compra'],
                'tasa_venta'             => $payload['tasa_venta'],
                'tasa_aplicada'          => null,
                'descripcion'            => $payload['descripcion'] ?? null,
                'estatus'                => 'sin_verificar',
                'estado_pool'            => 'pendiente',
                'origen'                 => $payload['origen'] ?? 'manual',
            ]);

            foreach ($payload['movimientos'] as $index => $movData) {
                $cuenta = Cuenta::findOrFail($movData['cuenta_id']);
                $operacion->movimientos()->create([
                    'cuenta_id'             => $cuenta->id,
                    'moneda_id'             => $cuenta->moneda_id,
                    'monto'                 => $movData['monto'],
                    'tasa_a_usd'            => $movData['tasa_a_usd'] ?? 1,
                    'monto_usd_equivalente' => round($movData['monto'] * ($movData['tasa_a_usd'] ?? 1), 4),
                    'orden'                 => $index + 1,
                ]);
            }

            // Calcular ganancia
            $operacion->load('movimientos.moneda');
            $ganancia = $this->calcularGananciaBrutaIntermediada($operacion);
            $operacion->update([
                'ganancia_bruta_usd' => $ganancia['usd'],
                'ganancia_bruta_ves' => $ganancia['ves'],
            ]);

            // Aplicar comisiones si corresponde
            $operacion->load(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);
            $this->comisionesService->aplicarAOperacion($operacion);

            // Actualizar saldo de cuentas afectadas automáticamente
            foreach ($payload['movimientos'] as $movData) {
                $cuenta = Cuenta::find($movData['cuenta_id']);
                if ($cuenta && $cuenta->saldo_cache_at) {
                    $nuevoSaldo = bcadd($cuenta->saldo_cache, $movData['monto'], 4);
                    $cuenta->update(['saldo_cache' => $nuevoSaldo]);
                }
            }

            return $operacion->fresh(['movimientos.cuenta', 'tipoOperacion']);
        });
    }

    /**
     * Valida movimientos específicos para operaciones intermediadas.
     *
     * @param array $movs
     * @return void
     * @throws ValidationException
     */
    private function validarMovimientosIntermediada(array $movs): void
    {
        if (count($movs) < 4) {
            throw ValidationException::withMessages([
                'movimientos' => 'La operación intermediada requiere al menos 4 movimientos.',
            ]);
        }

        $cuentaIds = array_column($movs, 'cuenta_id');
        $inactivas = Cuenta::whereIn('id', $cuentaIds)->where('activa', false)->get();
        if ($inactivas->isNotEmpty()) {
            $aliases = $inactivas->pluck('alias')->join(', ');
            throw ValidationException::withMessages([
                'movimientos' => "Las siguientes cuentas están inactivas: {$aliases}.",
            ]);
        }
        // No validamos cuadre estricto porque la ganancia es la diferencia
    }

    /**
     * Calcula la ganancia bruta para operaciones intermediadas.
     *
     * La ganancia es el spread entre la tasa de venta y la tasa de compra,
     * aplicado al monto en divisa (USD) de la operación.
     *
     * @param Operacion $operacion
     * @return array{usd: float, ves: float}
     */
    private function calcularGananciaBrutaIntermediada(Operacion $operacion): array
    {
        $tasaCompra = (float) $operacion->tasa_compra;
        $tasaVenta  = (float) $operacion->tasa_venta;

        // Buscar el monto en divisa (USD) desde los movimientos
        $montoDivisa = $operacion->movimientos
            ->filter(fn ($m) => in_array($m->moneda->codigo, ['USD', 'USDT', 'EUR', 'COP']))
            ->sum(fn ($m) => abs((float) $m->monto)) / 2; // cada par (ida y vuelta) suma 2 veces

        $gananciaVes = $montoDivisa * ($tasaVenta - $tasaCompra);
        $gananciaUsd = $tasaVenta > 0 ? $gananciaVes / $tasaVenta : 0;

        return [
            'usd' => round($gananciaUsd, 4),
            'ves' => round($gananciaVes, 2),
        ];
    }
}
