<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * Cuentas bancarias o de monedas, asociadas a titulares o clientes.
 *
 * @property int $id
 * @property int|null $titular_id
 * @property int|null $cliente_id
 * @property int|null $banco_id
 * @property int $moneda_id
 * @property string $alias
 * @property string $tipo
 * @property string|null $numero_cuenta
 * @property string|null $saldo_cache
 * @property \Illuminate\Support\Carbon|null $saldo_cache_at
 * @property bool $activa
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Titular|null $titular
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Banco|null $banco
 * @property-read \App\Models\Moneda|null $moneda
 */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Titular>
     */
    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cliente>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Banco>
     */
    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }
}
