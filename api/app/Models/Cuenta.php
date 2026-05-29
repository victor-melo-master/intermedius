<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cuenta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas';

    protected $fillable = [
        'titular_id',
        'banco_id',
        'moneda_id',
        'alias',
        'tipo',
        'numero_cuenta',
        'saldo_cache',
        'saldo_cache_at',
        'activa',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'activa'         => 'boolean',
            'saldo_cache'    => 'string',
            'saldo_cache_at' => 'datetime',
        ];
    }

    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }
}
