<?php

namespace App\Policies;

use App\Models\CategoriaGasto;
use App\Models\User;

/**
 * Policy para el modelo CategoriaGasto.
 * Solo admin puede crear/editar/eliminar; todos los roles autenticados pueden ver.
 */
class CategoriaGastoPolicy
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
     * Determine whether the user can view any categoria gasto records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific categoria gasto.
     */
    public function view(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create categoria gasto records.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update a categoria gasto.
     */
    public function update(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete a categoria gasto.
     */
    public function delete(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->hasRole('admin');
    }
}
