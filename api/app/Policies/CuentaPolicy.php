<?php

namespace App\Policies;

use App\Models\Cuenta;
use App\Models\User;

/**
 * Policy para el modelo Cuenta.
 * Admin puede crear cuentas en cualquier titular; operador puede crear solo en titular "Terceros".
 */
class CuentaPolicy
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
     * Determine whether the user can view any cuentas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific cuenta.
     */
    public function view(User $user, Cuenta $cuenta): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create cuentas.
     * Operador may create only for titular "Terceros".
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        // Operador puede crear cuentas para el titular "Terceros"
        $tercerosId = \App\Models\Titular::where('alias', 'terceros')->value('id');
        if ($tercerosId && request()->input('titular_id') == $tercerosId) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update a cuenta.
     */
    public function update(User $user, Cuenta $cuenta): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete a cuenta.
     */
    public function delete(User $user, Cuenta $cuenta): bool
    {
        return $user->hasRole('admin');
    }
}
