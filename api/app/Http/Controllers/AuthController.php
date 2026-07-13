<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de autenticación de usuarios.
 */
class AuthController extends Controller
{
    /**
     * Autentica al usuario con credenciales y devuelve un token de API.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // ── Verificar bloqueo por intentos fallidos ──────────────────
        $recentAttempts = LoginAttempt::where('email', $request->email)
            ->where('attempted_at', '>', now()->subMinutes(15))
            ->where('successful', false)
            ->count();

        if ($recentAttempts >= 5) {
            return response()->json([
                'message' => 'Demasiados intentos fallidos. Intente de nuevo en 15 minutos.',
            ], 429);
        }

        // ── Intentar autenticación ──────────────────────────────────
        $credentials = $request->only('email', 'password');
        $authenticated = Auth::attempt($credentials);

        // ── Registrar intento ───────────────────────────────────────
        LoginAttempt::create([
            'email'       => $request->email,
            'ip_address'  => $request->ip(),
            'attempted_at'=> now(),
            'successful'  => $authenticated,
        ]);

        if (! $authenticated) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        /** @var User $usuario */
        $usuario = Auth::user();

        if (is_null($usuario->email_verified_at)) {
            Auth::logout();
            return response()->json(['message' => 'Debe verificar su correo electrónico antes de iniciar sesión.'], 403);
        }

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

    /**
     * Revoca el token de acceso del usuario autenticado.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Devuelve la información del usuario autenticado.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->usuarioConRol($request->user()));
    }

    /**
     * Verifica el correo electrónico de un usuario (POST desde SPA).
     *
     * @param Request $request Debe incluir 'email' y 'hash' (sha1 del email)
     * @return JsonResponse
     */
    public function verificarEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'hash'  => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $request->hash)) {
            return response()->json(['message' => 'Token de verificación inválido.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya estaba verificado.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Correo verificado exitosamente.']);
    }

    /**
     * Verifica el correo electrónico de un usuario (GET con URL firmada, endpoint legacy).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $request->hash)) {
            return response()->json(['message' => 'Token de verificación inválido.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya estaba verificado.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Correo verificado exitosamente.']);
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
