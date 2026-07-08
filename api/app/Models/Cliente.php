<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Clientes del sistema (personas o entidades que realizan operaciones).
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $alias
 * @property string|null $documento
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $notas
 * @property string|null $saldo_cache_usd
 * @property \Illuminate\Support\Carbon|null $saldo_cache_at
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cuenta> $cuentas
 */
class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'alias',
        'documento',
        'telefono',
        'email',
        'notas',
        'saldo_cache_usd',
        'saldo_cache_at',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo'          => 'boolean',
            'saldo_cache_usd' => 'string',
            'saldo_cache_at'  => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Cuenta>
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }
}
