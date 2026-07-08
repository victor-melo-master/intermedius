<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
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

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->hasRole(['admin', 'operador']);
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Cliente $cliente): bool
    {
        return $user->hasRole('admin');
    }
}
