<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected function casts(): array
    {
        return [
            'fecha'                  => 'date',
            'verificado_at'          => 'datetime',
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

    public function tasaDiaria(): BelongsTo
    {
        return $this->belongsTo(TasaDiaria::class);
    }

    public function comisiones(): HasMany
    {
        return $this->hasMany(ComisionOperacion::class);
    }
}
