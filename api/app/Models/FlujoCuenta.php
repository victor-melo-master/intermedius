<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlujoCuenta extends Model
{
    protected $fillable = [
        'cuenta_id',
        'tipo',
        'monto',
        'moneda_id',
        'descripcion',
        'operacion_id',
        'transaccion_id',
        'registrado_por_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    public function transaccion(): BelongsTo
    {
        return $this->belongsTo(Transaccion::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
