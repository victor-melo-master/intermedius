<?php

namespace App\Services\Pool;

use App\Models\Operacion;
use App\Models\User;
use App\Services\Pool\PoolValidator;
use App\Services\Pool\PoolNotifier;
use Illuminate\Support\Facades\DB;

/**
 * Core pool logic for FIFO assignment, taking/releasing/paying/cancelling operations.
 * Coordinates pool state transitions and enforces business rules through PoolValidator.
 */
class PoolService
{
    public function __construct(
        private readonly PoolValidator $validator,
        private readonly PoolNotifier $notifier,
    ) {}

    /**
     * Assigns the next pending operations to a pagador (FIFO).
     *
     * @param  User   $pagador
     * @param  int    $limit   Max number of operations to assign.
     * @return \Illuminate\Support\Collection<int, Operacion>
     */
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
            }
        });

        if ($operaciones->isNotEmpty()) {
            $this->notifier->operacionesAsignadas($operaciones, $pagador);
        }

        return $operaciones;
    }

    /**
     * Releases previously assigned operations back to the pool.
     *
     * @param  Operacion[]|iterable  $operaciones
     * @param  User                  $pagador
     */
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
            }
        });
    }

    /**
     * Marks an operation as paid (pagada) if all its transactions are validated.
     *
     * @param  Operacion  $operacion
     * @param  User       $pagador
     */
    public function pagarOperacion(Operacion $operacion, User $pagador): void
    {
        $this->validator->assertPuedePagar($operacion, $pagador);
        $this->validator->assertTodasTransaccionesValidadas($operacion);

        $operacion->update([
            'estado_pool' => 'pagada',
            'estado'      => 'concluida',
            'pagada_at'   => now(),
        ]);

        $this->notifier->operacionPagada($operacion, $pagador);
    }

    /**
     * Cancels an operation and returns it to available state.
     *
     * @param  Operacion  $operacion
     * @param  User       $usuario
     * @param  string     $motivo
     */
    public function cancelarOperacion(Operacion $operacion, User $usuario, string $motivo): void
    {
        $this->validator->assertPuedeCancelar($operacion, $usuario);

        $operacion->update([
            'estado_pool'        => 'cancelada',
            'estado'             => 'cancelada',
            'cancelada_at'       => now(),
            'motivo_cancelacion' => $motivo,
        ]);

        $this->notifier->operacionCancelada($operacion, $usuario, $motivo);
    }
}
