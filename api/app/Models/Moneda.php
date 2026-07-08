<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Monedas (divisas) del sistema.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $simbolo
 * @property bool $es_fiat
 * @property bool $es_cripto
 * @property int $decimales
 * @property bool $activa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cuenta> $cuentas
 */
class Moneda extends Model
{
    use HasFactory;

    protected $table = 'monedas';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'es_fiat',
        'es_cripto',
        'decimales',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'es_fiat'   => 'boolean',
            'es_cripto' => 'boolean',
            'activa'    => 'boolean',
            'decimales' => 'integer',
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
