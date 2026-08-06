<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuarios del sistema (autenticación, roles, permisos).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $titular_id
 * @property bool $activo
 * @property string|null $avatar_path
 * @property string|null $telefono
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Titular|null $titular
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'titular_id',
        'activo',
        'last_login_at',
        'last_active_at',
        'avatar_path',
        'telefono',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
            'last_login_at'     => 'datetime',
            'last_active_at'    => 'datetime',
        ];
    }

    /**
     * Normaliza el nombre de usuario a minúsculas para mantener consistencia.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => trim(strtolower($value)),
        );
    }

    /**
     * Normaliza el correo electrónico a minúsculas para mantener consistencia.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => trim(strtolower($value)),
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Titular>
     */
    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SesionUsuario>
     */
    public function sesiones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SesionUsuario::class);
    }

    /**
     * Envía la notificación de verificación de correo, salvo que el ajuste
     * global 'envio_emails' esté desactivado.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (Ajuste::activo('envio_emails', true)) {
            $this->notify(new \App\Notifications\VerifyEmailNotification());
        }
    }
}
