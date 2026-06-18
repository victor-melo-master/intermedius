<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operaciones';

    protected $fillable = [
        'fecha',
        'tipo_operacion_id',
        'cliente_id',
        'categoria_gasto_id',
        'operador_id',
        'tasa_aplicada',
        'genera_comision',
        'monto_comision',
        'tipo_comision',
        'tasa_mercado_snapshot',
        'fuente_tasa_mercado',
        'tasa_sugerida',
        'tasa_diaria_id',
        'sin_tasa_referencia',
        'ganancia_bruta_usd',
        'ganancia_real_usd',
        'ganancia_bruta_ves',
        'ganancia_real_ves',
        'total_comisiones_usd',
        'total_comisiones_ves',
        'ganancia_neta_usd',
        'ganancia_neta_ves',
        'referencia',
        'descripcion',
        'estatus',
        'verificado_at',
        'verificado_por_id',
        'origen',
        'origen_referencia',
        'estado_pool',
        'pagador_id',
        'asignada_at',
        'pagada_at',
        'cancelada_at',
        'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha'                  => 'date',
            'verificado_at'          => 'datetime',
            'asignada_at'            => 'datetime',
            'pagada_at'              => 'datetime',
            'cancelada_at'           => 'datetime',
            'genera_comision'        => 'boolean',
            'monto_comision'         => 'decimal:4',
            'sin_tasa_referencia'     => 'boolean',
            'ganancia_bruta_usd'     => 'decimal:4',
            'ganancia_real_usd'      => 'decimal:4',
            'ganancia_bruta_ves'     => 'decimal:2',
            'ganancia_real_ves'      => 'decimal:2',
            'total_comisiones_usd'   => 'decimal:4',
            'total_comisiones_ves'   => 'decimal:2',
            'ganancia_neta_usd'      => 'decimal:4',
            'ganancia_neta_ves'      => 'decimal:2',
            'tasa_aplicada'          => 'decimal:8',
            'tasa_sugerida'          => 'decimal:8',
            'tasa_mercado_snapshot'  => 'decimal:8',
        ];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('orden');
    }

    public function tipoOperacion(): BelongsTo
    {
        return $this->belongsTo(TipoOperacion::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class);
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por_id');
    }

    public function pagador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagador_id');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_pool', 'pendiente');
    }

    public function scopeAsignadasA(Builder $query, int $userId): Builder
    {
        return $query->where('pagador_id', $userId)
            ->where('estado_pool', 'asignada');
    }

    public function tasaDiaria(): BelongsTo
    {
        return $this->belongsTo(TasaDiaria::class);
    }

    public function comisiones(): HasMany
    {
        return $this->hasMany(ComisionOperacion::class);
    }
}
