<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * Policy para el modelo Cliente.
 * Admin tiene control total; operador puede actualizar; lectura y contador solo ven.
 */
class ClientePolicy
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
     * Determine whether the user can view any clientes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific cliente.
     */
    public function view(User $user, Cliente $cliente): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create clientes.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update a cliente.
     */
    public function update(User $user, Cliente $cliente): bool
    {
        return $user->hasRole(['admin', 'operador']);
    }

    /**
     * Determine whether the user can delete (soft) a cliente.
     */
    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore a soft-deleted cliente.
     */
    public function restore(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('admin');
    }
}
