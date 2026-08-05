<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sesión de usuario (login/logout) para el historial de accesos.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $token_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $login_at
 * @property \Illuminate\Support\Carbon|null $logout_at
 * @property string|null $logout_tipo ('manual'|'expirada'|null)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User|null $user
 */
class SesionUsuario extends Model
{
    protected $table = 'sesiones_usuario';

    protected $fillable = [
        'user_id',
        'token_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'logout_tipo',
    ];

    protected function casts(): array
    {
        return [
            'login_at'  => 'datetime',
            'logout_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vida útil del token en minutos (para marcar sesiones como 'expirada').
     */
    public static function minutosExpiracion(): int
    {
        return (int) config('sanctum.expiration', 1440);
    }
}
