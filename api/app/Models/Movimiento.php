<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimientos individuales de fondos dentro de una operación.
 *
 * @property int $id
 * @property int $operacion_id
 * @property int $cuenta_id
 * @property int $moneda_id
 * @property string $monto
 * @property string $tasa_a_usd
 * @property string $monto_usd_equivalente
 * @property int $orden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Operacion|null $operacion
 * @property-read \App\Models\Cuenta|null $cuenta
 * @property-read \App\Models\Moneda|null $moneda
 */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Operacion>
     */
    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cuenta>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }
}
