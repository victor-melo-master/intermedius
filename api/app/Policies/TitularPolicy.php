<?php

namespace App\Policies;

use App\Models\Titular;
use App\Models\User;

class TitularPolicy
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

    public function view(User $user, Titular $titular): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Titular $titular): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Titular $titular): bool
    {
        return $user->hasRole('admin');
    }
}
