<?php

namespace App\Services\Pool;

use App\Models\Operacion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Validates pool state transitions, balances, and user permissions.
 * Should be called before any state mutation in PoolService.
 */
class PoolValidator
{
    /**
     * Throws if user is not allowed to take operations from the pool.
     */
    public function assertPuedeTomarOperaciones(User $pagador): void
    {
        if (!$pagador->hasPermissionTo('pool.tomar')) {
            throw ValidationException::withMessages([
                'pagador_id' => 'El usuario no tiene permiso para tomar operaciones del pool.',
            ]);
        }
    }

    /**
     * Throws if the operation cannot be released by this pagador.
     */
    public function assertPuedeSoltar(Operacion $operacion, User $pagador): void
    {
        if ($operacion->estado_pool !== 'asignada') {
            throw ValidationException::withMessages([
                'operacion_id' => 'Solo se pueden soltar operaciones en estado "asignada".',
            ]);
        }

        if ((int) $operacion->pagador_id !== (int) $pagador->id) {
            throw ValidationException::withMessages([
                'pagador_id' => 'No puedes soltar una operación que no te fue asignada.',
            ]);
        }
    }

    /**
     * Throws if the operation is not ready to be marked as paid.
     */
    public function assertPuedePagar(Operacion $operacion, User $pagador): void
    {
        if ($operacion->estado_pool !== 'asignada') {
            throw ValidationException::withMessages([
                'operacion_id' => 'Solo se pueden pagar operaciones en estado "asignada".',
            ]);
        }

        if ((int) $operacion->pagador_id !== (int) $pagador->id) {
            throw ValidationException::withMessages([
                'pagador_id' => 'No puedes pagar una operación que no te fue asignada.',
            ]);
        }

        if (!$pagador->hasPermissionTo('pool.pagar')) {
            throw ValidationException::withMessages([
                'pagador_id' => 'El usuario no tiene permiso para pagar operaciones.',
            ]);
        }
    }

    /**
     * Assert that all transactions associated with an operation are validated.
     *
     * @throws ValidationException
     */
    public function assertTodasTransaccionesValidadas(Operacion $operacion): void
    {
        $pendientes = $operacion->transacciones()
            ->where('estado', '!=', 'validada')
            ->count();

        if ($pendientes > 0) {
            throw ValidationException::withMessages([
                'pool' => "No se puede concluir la operación porque {$pendientes} transacción(es) no están validadas.",
            ]);
        }
    }

    /**
     * Throws if the operation cannot be cancelled.
     */
    public function assertPuedeCancelar(Operacion $operacion, User $usuario): void
    {
        if (!in_array($operacion->estado_pool, ['pendiente', 'asignada'], true)) {
            throw ValidationException::withMessages([
                'operacion_id' => 'Solo operaciones pendientes o asignadas pueden cancelarse.',
            ]);
        }

        if (!$usuario->hasPermissionTo('pool.cancelar')) {
            throw ValidationException::withMessages([
                'usuario_id' => 'El usuario no tiene permiso para cancelar operaciones.',
            ]);
        }
    }
}
