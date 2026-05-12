<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    use HasFactory;

    protected $table = 'movimientos';

    protected $fillable = [
        'operacion_id',
        'cuenta_id',
        'moneda_id',
        'monto',
        'tasa_a_usd',
        'monto_usd_equivalente',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'monto'                => 'decimal:4',
            'tasa_a_usd'           => 'decimal:8',
            'monto_usd_equivalente' => 'decimal:4',
        ];
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }
}
