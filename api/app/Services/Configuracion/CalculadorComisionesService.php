<?php

namespace App\Services\Configuracion;

use App\Models\ComisionCuenta;
use App\Models\ComisionMetodoPago;
use App\Models\ComisionOperacion;
use App\Models\ComisionOperador;
use App\Models\Moneda;
use App\Models\Operacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for calculating and applying commissions to operations.
 * Supports three commission types: account-level, operator-level, and payment-method fees.
 * Handles percentage/fixed amounts, currency conversion, and total recomputation.
 */
class CalculadorComisionesService
{
    public function __construct(private readonly TasaDiariaService $tasaService) {}

    /**
     * Calcula todas las comisiones aplicables a una operación.
     * NO persiste nada; solo retorna la colección.
     *
     * @return Collection<array{tipo, origen_model, descripcion, monto, moneda_id, monto_usd_equivalente, movimiento_id}>
     */
    public function calcularParaOperacion(Operacion $op): Collection
    {
        $op->loadMissing(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']);

        $resultado = collect();

        // ── 1. Comisiones de CUENTA ───────────────────────────────────────────
        foreach ($op->movimientos as $mov) {
            $direccion = $mov->monto > 0 ? 'ingreso' : 'egreso';

            $comisiones = ComisionCuenta::where('activa', true)
                ->where(function ($q) use ($mov) {
                    $q->where('cuenta_id', $mov->cuenta_id)
                      ->orWhere('banco_id', $mov->cuenta->banco_id);
                })
                ->whereIn('aplica_a', [$direccion, 'ambos'])
                ->vigentes($op->fecha)
                ->get();

            foreach ($comisiones as $com) {
                $monto = $com->tipo_calculo === 'porcentaje'
                    ? abs($mov->monto) * ((float) $com->valor / 100)
                    : (float) $com->valor;

                $montoUsd = $com->moneda_id === $mov->moneda_id
                    ? round($monto * (float) $mov->tasa_a_usd, 2)
                    : $this->convertirAUsd($monto, $com->moneda_id, $op->fecha);

                $porcentajeStr = $com->tipo_calculo === 'porcentaje' ? "{$com->valor}%" : "fijo {$com->valor}";

                $resultado->push([
                    'tipo'                  => 'cuenta',
                    'origen_model'          => $com,
                    'descripcion'           => "{$com->descripcion} sobre {$mov->cuenta->alias}: {$porcentajeStr}",
                    'monto'                 => round($monto, 2),
                    'moneda_id'             => $com->moneda_id,
                    'monto_usd_equivalente' => $montoUsd,
                    'movimiento_id'         => $mov->id,
                ]);
            }
        }

        // ── 2. Comisiones de OPERADOR ─────────────────────────────────────────
        $operador = $op->operador;
        if ($operador && $operador->titular_id) {
            $comisiones = ComisionOperador::where('titular_id', $operador->titular_id)
                ->where('activa', true)
                ->where(fn ($q) => $q
                    ->whereNull('tipo_operacion_id')
                    ->orWhere('tipo_operacion_id', $op->tipo_operacion_id)
                )
                ->vigentes($op->fecha)
                ->get();

            $montoOperacion = $this->calcularMontoOperacion($op);

            foreach ($comisiones as $com) {
                $base = $com->base_calculo === 'ganancia_bruta'
                    ? abs((float) $op->ganancia_bruta_usd)
                    : $montoOperacion;

                $monto = $com->tipo_calculo === 'porcentaje'
                    ? $base * ((float) $com->valor / 100)
                    : (float) $com->valor;

                $montoUsd = $this->convertirAUsd($monto, $com->moneda_id, $op->fecha);

                $resultado->push([
                    'tipo'                  => 'operador',
                    'origen_model'          => $com,
                    'descripcion'           => "Comisión operador {$operador->name} ({$com->descripcion})",
                    'monto'                 => round($monto, 2),
                    'moneda_id'             => $com->moneda_id,
                    'monto_usd_equivalente' => round($montoUsd, 2),
                    'movimiento_id'         => null,
                ]);
            }
        }

        // ── 3. Comisiones de MÉTODO DE PAGO ──────────────────────────────────
        $cuentaIds = $op->movimientos->pluck('cuenta_id')->unique();

        $comisiones = ComisionMetodoPago::where('activa', true)
            ->where(fn ($q) => $q
                ->whereNull('cuenta_id')
                ->orWhereIn('cuenta_id', $cuentaIds)
            )
            ->vigentes($op->fecha)
            ->get();

        foreach ($comisiones as $com) {
            $movsAfectados = $com->cuenta_id
                ? $op->movimientos->where('cuenta_id', $com->cuenta_id)
                : $op->movimientos;

            $totalMovs = $movsAfectados->sum(fn ($m) => abs((float) $m->monto));

            $monto = $com->tipo_calculo === 'porcentaje'
                ? $totalMovs * ((float) $com->valor / 100)
                : (float) $com->valor;

            $montoUsd = $this->convertirAUsd($monto, $com->moneda_id, $op->fecha);

            $resultado->push([
                'tipo'                  => 'metodo_pago',
                'origen_model'          => $com,
                'descripcion'           => "Fee {$com->nombre_metodo}",
                'monto'                 => round($monto, 2),
                'moneda_id'             => $com->moneda_id,
                'monto_usd_equivalente' => round($montoUsd, 2),
                'movimiento_id'         => null,
            ]);
        }

        return $resultado;
    }

    /**
     * Calcula y persiste comisiones para la operación (idempotente: borra las anteriores primero).
     * Luego recalcula totales y ganancia neta.
     */
    public function aplicarAOperacion(Operacion $op): void
    {
        DB::transaction(function () use ($op) {
            ComisionOperacion::where('operacion_id', $op->id)->delete();

            $comisiones = $this->calcularParaOperacion($op);

            foreach ($comisiones as $com) {
                ComisionOperacion::create([
                    'operacion_id'          => $op->id,
                    'tipo'                  => $com['tipo'],
                    'origen_type'           => get_class($com['origen_model']),
                    'origen_id'             => $com['origen_model']->id,
                    'descripcion'           => $com['descripcion'],
                    'monto'                 => $com['monto'],
                    'moneda_id'             => $com['moneda_id'],
                    'monto_usd_equivalente' => $com['monto_usd_equivalente'],
                    'movimiento_id'         => $com['movimiento_id'],
                ]);
            }

            $this->recalcularTotalesOperacion($op);
        });
    }

    /**
     * Suma todas las comisiones de la operación y actualiza los campos de totales y ganancia neta.
     */
    public function recalcularTotalesOperacion(Operacion $op): void
    {
        $op->loadMissing('comisiones.moneda');
        $comisiones = ComisionOperacion::where('operacion_id', $op->id)->with('moneda')->get();

        $totalUsd = round($comisiones->sum('monto_usd_equivalente'), 2);

        // VES: suma directa si moneda es VES, conversión vía tasa operativa para otros
        $tasaVes = (float) ($op->tasa_aplicada ?? $op->tasa_sugerida ?? 0);
        $totalVes = 0.0;
        foreach ($comisiones as $com) {
            if ($com->moneda && $com->moneda->codigo === 'VES') {
                $totalVes += (float) $com->monto;
            } elseif ($tasaVes > 0) {
                $totalVes += (float) $com->monto_usd_equivalente * $tasaVes;
            }
        }

        $op->update([
            'total_comisiones_usd' => $totalUsd,
            'total_comisiones_ves' => round($totalVes, 2),
            'ganancia_neta_usd'    => round((float) $op->ganancia_bruta_usd - $totalUsd, 2),
            'ganancia_neta_ves'    => round((float) $op->ganancia_bruta_ves - $totalVes, 2),
        ]);
    }

    /**
     * Edita una comisión ya aplicada (solo admin/super_admin).
     * Registra quién editó, cuándo y por qué, y recalcula los totales.
     */
    /**
     * Edits an already applied commission (admin/super_admin only).
     * Logs who edited, when, and why, then recalculates totals.
     *
     * @param  ComisionOperacion  $comision
     * @param  array              $nuevoValor
     * @param  \App\Models\User   $admin
     * @param  string             $razon
     */
    public function editarComision(ComisionOperacion $comision, array $nuevoValor, \App\Models\User $admin, string $razon): void
    {
        DB::transaction(function () use ($comision, $nuevoValor, $admin, $razon) {
            $comision->update([
                'monto'                 => $nuevoValor['monto']                 ?? $comision->monto,
                'monto_usd_equivalente' => $nuevoValor['monto_usd_equivalente'] ?? $comision->monto_usd_equivalente,
                'descripcion'           => $nuevoValor['descripcion']           ?? $comision->descripcion,
                'editada_por_id'        => $admin->id,
                'editada_at'            => now(),
                'razon_edicion'         => $razon,
            ]);

            $this->recalcularTotalesOperacion($comision->operacion);
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Métodos privados
    // ──────────────────────────────────────────────────────────────────────────

    private function calcularMontoOperacion(Operacion $op): float
    {
        return (float) $op->movimientos
            ->where('monto', '>', 0)
            ->sum('monto_usd_equivalente');
    }

    /**
     * Convierte un monto de la moneda dada a USD usando la tasa diaria vigente en la fecha.
     * Fallback: si no hay tasa, retorna 0 (la comisión quedará con equivalente 0, visible para auditoría).
     */
    private function convertirAUsd(float $monto, int $monedaId, Carbon|string $fecha): float
    {
        $moneda = Moneda::find($monedaId);
        if (!$moneda) return 0.0;
        if ($moneda->codigo === 'USD') return $monto;

        $usdId = Moneda::where('codigo', 'USD')->value('id');
        $fecha = is_string($fecha) ? Carbon::parse($fecha) : $fecha;

        $tasa = $this->tasaService->obtenerVigente($monedaId, $usdId, $fecha->endOfDay());

        if ($tasa) {
            return round($monto / (float) $tasa->tasa_venta, 2);
        }

        // Fallback: tasa de mercado más reciente
        $tasaMercado = \App\Models\TasaMercado::where('fuente', 'bcv')
            ->where('capturado_en', '<=', $fecha->endOfDay())
            ->orderByDesc('capturado_en')
            ->value('valor');

        return $tasaMercado ? round($monto / (float) $tasaMercado, 2) : 0.0;
    }
}
