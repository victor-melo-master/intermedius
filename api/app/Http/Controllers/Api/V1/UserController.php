<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use App\Models\SesionUsuario;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Controlador de usuarios del sistema.
 * Gestiona el CRUD de usuarios (alta, baja, modificación y listado).
 */
class UserController extends Controller
{
    /**
     * Obtiene la lista de todos los usuarios activos con su titular.
     * Acepta filtros por query params: q (nombre/email), rol, activo.
     *
     * @param Request $request Filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('titular')->withTrashed(false);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($rol = $request->query('rol')) {
            $query->role($rol);
        }

        $usuarios = $query->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->formatUser($u));

        return response()->json($usuarios);
    }

    /**
     * Verifica si un nombre de usuario o correo ya está en uso.
     *
     * @param Request $request Parámetros: campo (name|email), valor, exclude_id (opcional)
     * @return JsonResponse {disponible: bool}
     */
    public function disponible(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campo'      => ['required', 'string', 'in:name,email'],
            'valor'      => ['required', 'string', 'max:255'],
            'exclude_id' => ['nullable', 'integer'],
        ]);

        $query = User::withTrashed()->where($validated['campo'], $validated['valor']);

        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', (int) $validated['exclude_id']);
        }

        return response()->json(['disponible' => $query->doesntExist()]);
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
            'password'   => ['required', 'string', $this->reglaPassword()],
            'rol'        => ['required', 'string', 'in:admin,operador,contador,lectura'],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activo'     => ['boolean'],
            'avatar'     => ['nullable', 'image', 'mimes:jpeg,png,gif,webp,bmp', 'max:2048'],
        ], $this->mensajesValidacion());

        $usuario = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'titular_id' => $validated['titular_id'] ?? null,
            'activo'     => $validated['activo'] ?? true,
        ]);

        $usuario->assignRole($validated['rol']);
        $this->procesarAvatar($request, $usuario);

        // Enviar email de verificación (solo si el envío de correos está activo)
        if (Ajuste::activo('envio_emails', true)) {
            $usuario->notify(new VerifyEmailNotification());
        }

        $respuesta = $this->formatUser($usuario->load('titular'));
        $this->agregarAdvertenciasPassword($respuesta, $validated['password']);

        return response()->json($respuesta, 201);
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
        if ($request->filled('password')) {
            $usuario->tokens()->delete();
        }
        $validated = $request->validate([
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'email'      => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password'   => ['nullable', 'string', $this->reglaPassword()],
            'rol'        => ['sometimes', 'required', 'string', 'in:admin,operador,contador,lectura'],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activo'     => ['sometimes', 'boolean'],
            'avatar'     => ['nullable', 'image', 'mimes:jpeg,png,gif,webp,bmp', 'max:2048'],
        ], $this->mensajesValidacion());

        $datos = collect($validated)->except(['password', 'rol'])->toArray();

        if (!empty($validated['password'])) {
            $datos['password'] = Hash::make($validated['password']);
        }

        $usuario->update($datos);
        $this->procesarAvatar($request, $usuario);

        if (isset($validated['rol'])) {
            $usuario->syncRoles([$validated['rol']]);
        }

        $respuesta = $this->formatUser($usuario->fresh('titular'));

        if (!empty($validated['password'])) {
            $this->agregarAdvertenciasPassword($respuesta, $validated['password']);
        }

        return response()->json($respuesta);
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

    /**
     * Mensajes personalizados de validación en español.
     * Sobrescribe el mensaje genérico de contraseña comprometida (HIBP).
     */
    private function mensajesValidacion(): array
    {
        return [
            'password.uncompromised' => 'Por seguridad, esta contraseña no se permite porque aparece en filtraciones públicas conocidas. Elige una contraseña diferente y que no uses en otros sitios.',
        ];
    }

    /**
     * Indica si la opción 'password_segura' está activa (rechaza contraseñas
     * comprometidas en filtraciones públicas vía HIBP).
     */
    private function passwordSeguraActivada(): bool
    {
        return (bool) Ajuste::obtener('password_segura', true);
    }

    /**
     * Construye la regla de contraseña. Si 'password_segura' está activa,
     * añade la comprobación de contraseñas comprometidas (uncompromised).
     */
    private function reglaPassword(): Password
    {
        $regla = Password::min(8)->mixedCase()->numbers()->symbols();

        if ($this->passwordSeguraActivada()) {
            $regla->uncompromised();
        }

        return $regla;
    }

    /**
     * Si 'password_segura' está desactivada y la contraseña aparece en
     * filtraciones conocidas, agrega una advertencia (no bloqueante) a la respuesta.
     */
    private function agregarAdvertenciasPassword(array &$respuesta, string $password): void
    {
        if ($this->passwordSeguraActivada()) {
            return;
        }

        // Str::isUncompromised devuelve true si la contraseña NO está comprometida.
        if (! Str::isUncompromised($password)) {
            $respuesta['advertencias'] = [
                'La contraseña elegida aparece en filtraciones públicas conocidas. Se recomienda usar una más segura.',
            ];
        }
    }

    /**
     * Sirve el avatar de un usuario (imagen WebP) autenticado por token.
     * La imagen se expone con caché larga ya que la ruta cambia al reemplazarse.
     *
     * @param Request $request Debe incluir ?token=<sanctum token>
     * @param User $usuario Usuario dueño del avatar
     * @return \Illuminate\Http\Response
     */
    public function avatar(Request $request, User $usuario)
    {
        $token = $request->query('token');
        if (!$token || !PersonalAccessToken::findToken($token)) {
            abort(401);
        }

        if (!$usuario->avatar_path || !Storage::disk('s3')->exists($usuario->avatar_path)) {
            abort(404, 'El usuario no tiene avatar.');
        }

        $archivo = Storage::disk('s3')->get($usuario->avatar_path);

        return response($archivo, 200)
            ->header('Content-Type', 'image/webp')
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }

    /**
     * Historial de sesiones (login/logout) de un usuario.
     * Las sesiones abiertas se reportan como 'vigente' o 'expirada'
     * según la vigencia del token Sanctum.
     *
     * @param User $usuario Usuario del historial
     * @return JsonResponse Lista de sesiones con tipo_cierre
     */
    public function sesiones(User $usuario): JsonResponse
    {
        $minutos = SesionUsuario::minutosExpiracion();

        $sesiones = SesionUsuario::where('user_id', $usuario->id)
            ->orderByDesc('login_at')
            ->get()
            ->map(function (SesionUsuario $s) use ($minutos) {
                $vigente = is_null($s->logout_at);
                $expirada = $vigente && $s->login_at->copy()->addMinutes($minutos)->lt(now());

                return [
                    'id'          => $s->id,
                    'ip_address'  => $s->ip_address,
                    'user_agent'  => $s->user_agent,
                    'login_at'    => $s->login_at,
                    'logout_at'   => $s->logout_at,
                    'tipo_cierre' => $s->logout_tipo ?? ($expirada ? 'expirada' : 'vigente'),
                ];
            });

        return response()->json($sesiones);
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
            'avatar_path'   => $u->avatar_path,
            'last_login_at' => $u->last_login_at,
            'created_at'    => $u->created_at,
        ];
    }

    /**
     * Procesa el avatar subido en el request: lo convierte a WebP y lo guarda
     * en s3, actualizando avatar_path del usuario. Borra el anterior si existía.
     *
     * @param Request $request Request con posible archivo 'avatar'
     * @param User $usuario Usuario dueño del avatar
     */
    private function procesarAvatar(Request $request, User $usuario): void
    {
        if (!$request->hasFile('avatar')) {
            return;
        }

        $ruta = app(AvatarService::class)->guardar(
            $request->file('avatar'),
            $usuario->id,
            $usuario->avatar_path
        );

        $usuario->update(['avatar_path' => $ruta]);
    }
}
