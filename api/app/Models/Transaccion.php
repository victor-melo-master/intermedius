<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaccion extends Model
{
    protected $table = 'transacciones';

    protected $fillable = [
        'operacion_id',
        'cuenta_origen_id',
        'cuenta_destino_id',
        'moneda_id',
        'monto',
        'estado',
        'motivo_rechazo',
        'comprobante',
        'validada_en',
        'validada_por_id',
        'orden',
    ];

    protected $casts = [
        'monto' => 'decimal:4',
        'validada_en' => 'datetime',
    ];

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

    public function validadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validada_por_id');
    }
}
