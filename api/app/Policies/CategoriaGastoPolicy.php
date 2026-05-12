<?php

namespace App\Policies;

use App\Models\CategoriaGasto;
use App\Models\User;

class CategoriaGastoPolicy
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

    public function view(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->hasRole('admin');
    }
}
