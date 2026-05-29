<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }
}
