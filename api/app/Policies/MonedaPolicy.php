<?php

namespace App\Policies;

use App\Models\Moneda;
use App\Models\User;

/**
 * Policy para el modelo Moneda.
 * Solo admin puede crear/editar/eliminar; todos los roles autenticados pueden ver.
 */
class MonedaPolicy
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
     * Determine whether the user can view any monedas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific moneda.
     */
    public function view(User $user, Moneda $moneda): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create monedas.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update a moneda.
     */
    public function update(User $user, Moneda $moneda): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete a moneda.
     */
    public function delete(User $user, Moneda $moneda): bool
    {
        return $user->hasRole('admin');
    }
}
