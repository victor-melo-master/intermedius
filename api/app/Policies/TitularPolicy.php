<?php

namespace App\Policies;

use App\Models\Titular;
use App\Models\User;

/**
 * Policy para el modelo Titular.
 * Define permisos CRUD: solo admin puede crear/editar/eliminar;
 * admin, operador, contador y lectura pueden ver.
 */
class TitularPolicy
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
     * Determine whether the user can view any titulares.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can view a specific titular.
     */
    public function view(User $user, Titular $titular): bool
    {
        return $user->hasRole(['admin', 'operador', 'contador', 'lectura']);
    }

    /**
     * Determine whether the user can create titulares.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update a titular.
     */
    public function update(User $user, Titular $titular): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete a titular.
     */
    public function delete(User $user, Titular $titular): bool
    {
        return $user->hasRole('admin');
    }
}
