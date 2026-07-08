<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bancos o entidades financieras registradas en el sistema.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $codigo
 * @property string|null $pais
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cuenta> $cuentas
 */
class Banco extends Model
{
    use HasFactory;

    protected $table = 'bancos';

    protected $fillable = [
        'nombre',
        'codigo',
        'pais',
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
}
