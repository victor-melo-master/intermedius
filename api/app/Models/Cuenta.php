<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Cuenta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas';

    protected $fillable = [
        'titular_id',
        'cliente_id',
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

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Cuenta $cuenta) {
            if ($cuenta->titular_id && $cuenta->cliente_id) {
                throw ValidationException::withMessages([
                    'titular_id'  => ['Una cuenta no puede tener titular y cliente simultáneamente.'],
                    'cliente_id'  => ['Una cuenta no puede tener titular y cliente simultáneamente.'],
                ]);
            }
            if (! $cuenta->titular_id && ! $cuenta->cliente_id) {
                throw ValidationException::withMessages([
                    'titular_id'  => ['Una cuenta debe pertenecer a un titular o a un cliente.'],
                    'cliente_id'  => ['Una cuenta debe pertenecer a un titular o a un cliente.'],
                ]);
            }
        });
    }

    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
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
