<?php

namespace App\Policies;

use App\Models\Banco;
use App\Models\User;

/**
 * Policy para el modelo Banco.
 * Solo admin puede crear/editar/eliminar; todos los roles autenticados pueden ver.
 */
class BancoPolicy
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
     * Determine whether the user can view any bancos.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific banco.
     */
    public function view(User $user, Banco $banco): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create bancos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update a banco.
     */
    public function update(User $user, Banco $banco): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete a banco.
     */
    public function delete(User $user, Banco $banco): bool
    {
        return $user->hasRole('admin');
    }
}
