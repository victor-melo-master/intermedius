<?php

namespace App\Policies;

use App\Models\Operacion;
use App\Models\User;

class OperacionPolicy
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

    public function view(User $user, Operacion $operacion): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'operador']);
    }

    public function verificar(User $user, Operacion $operacion): bool
    {
        return $user->hasRole(['admin', 'contador']);
    }
}
