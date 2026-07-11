<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Controlador de usuarios del sistema.
 * Gestiona el CRUD de usuarios (alta, baja, modificación y listado).
 */
class UserController extends Controller
{
    /**
     * Obtiene la lista de todos los usuarios activos con su titular.
     */
    public function index(): JsonResponse
    {
        $usuarios = User::with('titular')
            ->withTrashed(false)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->formatUser($u));

        return response()->json($usuarios);
    }

    /**
     * Crea un nuevo usuario con rol y lo asigna al sistema.
     *
     * @param Request $request Datos del usuario (name, email, password, rol, titular_id, activo)
     * @return JsonResponse Usuario creado con código 201
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
            'rol'        => ['required', 'string', 'in:super_admin,admin,operador,contador,lectura'],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activo'     => ['boolean'],
        ]);

        $usuario = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'titular_id' => $validated['titular_id'] ?? null,
            'activo'     => $validated['activo'] ?? true,
        ]);

        $usuario->assignRole($validated['rol']);

        return response()->json($this->formatUser($usuario->load('titular')), 201);
    }

    /**
     * Actualiza los datos de un usuario existente.
     *
     * @param Request $request Datos a actualizar (parcial o total)
     * @param User $usuario Modelo del usuario a modificar
     * @return JsonResponse Usuario actualizado
     */
    public function update(Request $request, User $usuario): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'email'      => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password'   => ['nullable', 'string', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
            'rol'        => ['sometimes', 'required', 'string', 'in:super_admin,admin,operador,contador,lectura'],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activo'     => ['sometimes', 'boolean'],
        ]);

        $datos = collect($validated)->except(['password', 'rol'])->toArray();

        if (!empty($validated['password'])) {
            $datos['password'] = Hash::make($validated['password']);
        }

        $usuario->update($datos);

        if (isset($validated['rol'])) {
            $usuario->syncRoles([$validated['rol']]);
        }

        return response()->json($this->formatUser($usuario->fresh('titular')));
    }

    /**
     * Desactiva (borrado lógico) un usuario.
     *
     * @param User $usuario Modelo del usuario a desactivar
     * @return JsonResponse Usuario desactivado
     */
    public function destroy(User $usuario): JsonResponse
    {
        $usuario->update(['activo' => false]);

        return response()->json($this->formatUser($usuario->fresh('titular')));
    }

    private function formatUser(User $u): array
    {
        return [
            'id'            => $u->id,
            'name'          => $u->name,
            'email'         => $u->email,
            'activo'        => $u->activo,
            'roles'         => $u->getRoleNames(),
            'titular_id'    => $u->titular_id,
            'titular'       => $u->titular ? ['id' => $u->titular->id, 'alias' => $u->titular->alias, 'nombre' => $u->titular->nombre] : null,
            'last_login_at' => $u->last_login_at,
            'created_at'    => $u->created_at,
        ];
    }
}
