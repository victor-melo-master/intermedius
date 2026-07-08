<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Titulares (personas o entidades propietarias de cuentas).
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $alias
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cuenta> $cuentas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CategoriaGasto> $categoriasGasto
 */
class Titular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'titulares';

    protected $fillable = [
        'nombre',
        'alias',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Cuenta>
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CategoriaGasto>
     */
    public function categoriasGasto(): HasMany
    {
        return $this->hasMany(CategoriaGasto::class);
    }
}
