<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra la última actividad del usuario autenticado.
 *
 * Escribe en `users.last_active_at` a lo sumo una vez por minuto por usuario
 * (para evitar una escritura por cada request) y sin tocar `updated_at`.
 */
class MarcarActividad
{
    private const INTERVALO_SEGUNDOS = 60;

    /**
     * @param Request $request
     * @param Closure $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $usuario = $request->user();

        if ($usuario && $this->debeMarcar($usuario->last_active_at)) {
            $usuario->timestamps = false;
            $usuario->forceFill(['last_active_at' => now()])->save();
        }

        return $response;
    }

    /**
     * Indica si corresponde actualizar la marca de actividad.
     *
     * @param Carbon|null $ultimaActividad
     */
    private function debeMarcar(?Carbon $ultimaActividad): bool
    {
        if (is_null($ultimaActividad)) {
            return true;
        }

        return $ultimaActividad->lt(now()->subSeconds(self::INTERVALO_SEGUNDOS));
    }
}
