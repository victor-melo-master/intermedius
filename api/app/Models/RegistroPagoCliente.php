<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroPagoCliente extends Model
{
    protected $table = 'registros_pago_cliente';

    protected $fillable = [
        'cliente_id',
        'metodo_pago',
        'alias',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
