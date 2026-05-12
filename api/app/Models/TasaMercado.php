<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function monedaBase(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_base_id');
    }

    public function monedaCotizada(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_cotizada_id');
    }
}
