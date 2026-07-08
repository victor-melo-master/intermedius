<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tasas de cambio obtenidas de fuentes externas (market data).
 *
 * @property int $id
 * @property string $fuente
 * @property int $moneda_base_id
 * @property int $moneda_cotizada_id
 * @property string $valor
 * @property \Illuminate\Support\Carbon|null $capturado_en
 * @property array $payload_original
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Moneda|null $monedaBase
 * @property-read \App\Models\Moneda|null $monedaCotizada
 */
class TasaMercado extends Model
{
    use HasFactory;

    protected $table = 'tasas_mercado';

    protected $fillable = [
        'fuente',
        'moneda_base_id',
        'moneda_cotizada_id',
        'valor',
        'capturado_en',
        'payload_original',
    ];

    protected function casts(): array
    {
        return [
            'valor'            => 'decimal:8',
            'capturado_en'     => 'datetime',
            'payload_original' => 'array',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function monedaBase(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_base_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function monedaCotizada(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_cotizada_id');
    }
}
