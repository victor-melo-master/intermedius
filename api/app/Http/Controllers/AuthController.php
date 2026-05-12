<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        /** @var User $usuario */
        $usuario = Auth::user();

        if (! $usuario->activo) {
            Auth::logout();
            return response()->json(['message' => 'Usuario inactivo.'], 403);
        }

        $usuario->update(['last_login_at' => now()]);

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->usuarioConRol($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->usuarioConRol($request->user()));
    }

    private function usuarioConRol(User $usuario): array
    {
        return [
            'id'            => $usuario->id,
            'name'          => $usuario->name,
            'email'         => $usuario->email,
            'roles'         => $usuario->getRoleNames(),
            'titular_id'    => $usuario->titular_id,
            'last_login_at' => $usuario->last_login_at,
        ];
    }
}
