<?php

namespace App\Policies;

use App\Models\Banco;
use App\Models\User;

class BancoPolicy
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

    public function view(User $user, Banco $banco): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Banco $banco): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Banco $banco): bool
    {
        return $user->hasRole('admin');
    }
}
