<?php

namespace App\Policies;

use App\Models\Cuenta;
use App\Models\User;

class CuentaPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    public function view(User $user, Cuenta $cuenta): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

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

    public function update(User $user, Cuenta $cuenta): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Cuenta $cuenta): bool
    {
        return $user->hasRole('admin');
    }
}
