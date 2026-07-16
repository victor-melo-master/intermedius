<?php

namespace App\Services\Pool;

use App\Events\OperacionAsignada;
use App\Events\OperacionPagada;
use App\Events\OperacionSoltada;
use App\Events\SlaExcedida;
use App\Models\Movimiento;
use App\Models\Operacion;
use App\Models\User;
use App\Services\Pool\PoolNotifier;
use App\Services\Pool\PoolValidator;
use Illuminate\Support\Facades\DB;

class PoolService
{
    public function __construct(
        private readonly PoolValidator $validator,
        private readonly PoolNotifier $notifier,
    ) {}

    public function tomarOperaciones(User $pagador, int $limit = 5): \Illuminate\Support\Collection
    {
        $this->validator->assertPuedeTomarOperaciones($pagador);

        $operaciones = Operacion::where('estado', 'en_espera')
            ->where('estado_pool', 'pendiente')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        DB::transaction(function () use ($operaciones, $pagador) {
            foreach ($operaciones as $op) {
                $op->update([
                    'estado_pool'  => 'asignada',
                    'estado'       => 'en_proceso',
                    'pagador_id'   => $pagador->id,
                    'asignada_at'  => now(),
                ]);

                event(new OperacionAsignada($op));
            }
        });

        if ($operaciones->isNotEmpty()) {
            $this->notifier->operacionesAsignadas($operaciones, $pagador);
        }

        return $operaciones;
    }

    public function soltarOperaciones(iterable $operaciones, User $pagador): void
    {
        foreach ($operaciones as $op) {
            $this->validator->assertPuedeSoltar($op, $pagador);
        }

        DB::transaction(function () use ($operaciones) {
            foreach ($operaciones as $op) {
                $op->update([
                    'estado_pool'  => 'pendiente',
                    'estado'       => 'en_espera',
                    'pagador_id'   => null,
                    'asignada_at'  => null,
                ]);

                event(new OperacionSoltada($op));
            }
        });
    }

    public function pagarOperacion(Operacion $operacion, User $pagador): void
    {
        $this->validator->assertPuedePagar($operacion, $pagador);
        $this->validator->assertTodasTransaccionesValidadas($operacion);

        DB::transaction(function () use ($operacion, $pagador) {
            $this->crearMovimientos($operacion);

            $operacion->update([
                'estado_pool' => 'pagada',
                'estado'      => 'concluida',
                'pagada_at'   => now(),
            ]);

            event(new OperacionPagada($operacion));

            $this->notifier->operacionPagada($operacion, $pagador);
        });
    }

    public function cancelarOperacion(Operacion $operacion, User $usuario, string $motivo): void
    {
        $this->validator->assertPuedeCancelar($operacion, $usuario);

        DB::transaction(function () use ($operacion, $usuario, $motivo) {
            foreach ($operacion->transacciones()->where('estado', 'pendiente')->get() as $tx) {
                $tx->update(['estado' => 'cancelada']);
            }

            $operacion->update([
                'estado_pool'        => 'cancelada',
                'estado'             => 'cancelada',
                'cancelada_at'       => now(),
                'motivo_cancelacion' => $motivo,
            ]);

            $this->notifier->operacionCancelada($operacion, $usuario, $motivo);
        });
    }

    private function crearMovimientos(Operacion $operacion): void
    {
        $tasaAplicada = $operacion->tasa_aplicada ?? $operacion->tasa_mercado_snapshot ?? 1;

        foreach ($operacion->transacciones()->where('estado', 'validada')->get() as $tx) {
            Movimiento::create([
                'operacion_id'          => $operacion->id,
                'cuenta_id'             => $tx->cuenta_origen_id,
                'moneda_id'             => $tx->moneda_id,
                'monto'                 => -(float) $tx->monto,
                'tasa_a_usd'            => $tasaAplicada,
                'monto_usd_equivalente' => round(-(float) $tx->monto * (float) $tasaAplicada, 2),
                'orden'                 => $tx->orden * 2 - 1,
            ]);

            Movimiento::create([
                'operacion_id'          => $operacion->id,
                'cuenta_id'             => $tx->cuenta_destino_id,
                'moneda_id'             => $tx->moneda_id,
                'monto'                 => (float) $tx->monto,
                'tasa_a_usd'            => $tasaAplicada,
                'monto_usd_equivalente' => round((float) $tx->monto * (float) $tasaAplicada, 2),
                'orden'                 => $tx->orden * 2,
            ]);
        }
    }
}
