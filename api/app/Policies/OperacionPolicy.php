<?php

namespace App\Policies;

use App\Models\Operacion;
use App\Models\User;

/**
 * Policy para el modelo Operacion.
 * Admin/operador pueden crear; admin/contador pueden verificar;
 * super_admin tiene acceso total via before().
 */
class OperacionPolicy
{
    /**
     * Grant all permissions to super_admin.
     */
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any operaciones.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific operacion.
     */
    public function view(User $user, Operacion $operacion): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create operaciones.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'operador']);
    }

    /**
     * Determine whether the user can verify (approve) an operacion.
     */
    public function verificar(User $user, Operacion $operacion): bool
    {
        return $user->hasRole(['admin', 'contador']);
    }

    /**
     * Determine whether the user can update an operacion.
     */
    public function update(User $user, Operacion $operacion): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador']);
    }

    /**
     * Determine whether the user can cancel an operacion.
     * Admin, super_admin, or the operator who created the solicitud.
     */
    public function cancel(User $user, Operacion $operacion): bool
    {
        if ($user->hasRole(['admin'])) {
            return true;
        }

        // El operador que creó la solicitud también puede cancelar
        return $user->id === $operacion->operador_id;
    }
}
