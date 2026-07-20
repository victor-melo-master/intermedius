<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaccion extends Model
{
    use HasFactory;

    protected $table = 'transacciones';

    protected $fillable = [
        'operacion_id',
        'cuenta_origen_id',
        'cuenta_destino_id',
        'moneda_id',
        'monto',
        'tasa_aplicada',
        'tasas_snapshot',
        'metodo_pago',
        'comprobante',
        'estado',
        'motivo_rechazo',
        'confirmada_en',
        'confirmada_por_id',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'monto'           => 'decimal:2',
            'tasa_aplicada'   => 'decimal:8',
            'tasas_snapshot'  => 'array',
            'confirmada_en'   => 'datetime',
        ];
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    public function cuentaOrigen(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_origen_id');
    }

    public function cuentaDestino(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_destino_id');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function confirmadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmada_por_id');
    }
}
