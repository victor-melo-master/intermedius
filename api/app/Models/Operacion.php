<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Operaciones financieras del sistema (compra/venta de divisas, gastos, etc.).
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $fecha
 * @property int $tipo_operacion_id
 * @property int|null $cliente_id
 * @property int|null $cliente_emisor_id
 * @property int|null $cliente_receptor_id
 * @property int|null $categoria_gasto_id
 * @property int|null $operador_id
 * @property string|null $tasa_aplicada
 * @property string|null $tasa_compra
 * @property string|null $tasa_venta
 * @property bool $genera_comision
 * @property string|null $monto_comision
 * @property string|null $tipo_comision
 * @property string|null $tasa_mercado_snapshot
 * @property string|null $fuente_tasa_mercado
 * @property string|null $tasa_sugerida
 * @property int|null $tasa_diaria_id
 * @property bool $sin_tasa_referencia
 * @property string|null $ganancia_bruta_usd
 * @property string|null $ganancia_real_usd
 * @property string|null $ganancia_bruta_ves
 * @property string|null $ganancia_real_ves
 * @property string|null $total_comisiones_usd
 * @property string|null $total_comisiones_ves
 * @property string|null $ganancia_neta_usd
 * @property string|null $ganancia_neta_ves
 * @property string|null $referencia
 * @property string|null $descripcion
 * @property string|null $estatus
 * @property \Illuminate\Support\Carbon|null $verificado_at
 * @property int|null $verificado_por_id
 * @property string|null $origen
 * @property string|null $origen_referencia
 * @property string|null $estado_pool
 * @property int|null $pagador_id
 * @property \Illuminate\Support\Carbon|null $asignada_at
 * @property \Illuminate\Support\Carbon|null $pagada_at
 * @property \Illuminate\Support\Carbon|null $cancelada_at
 * @property string|null $motivo_cancelacion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Movimiento> $movimientos
 * @property-read \App\Models\TipoOperacion|null $tipoOperacion
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\CategoriaGasto|null $categoriaGasto
 * @property-read \App\Models\User|null $operador
 * @property-read \App\Models\User|null $verificadoPor
 * @property-read \App\Models\User|null $pagador
 * @property-read \App\Models\TasaDiaria|null $tasaDiaria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ComisionOperacion> $comisiones
 * @property-read \App\Models\Cliente|null $clienteEmisor
 * @property-read \App\Models\Cliente|null $clienteReceptor
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static pendientes()
 * @method static \Illuminate\Database\Eloquent\Builder|static asignadasA(int $userId)
 */
class Operacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operaciones';

    protected $fillable = [
        'fecha',
        'tipo_operacion_id',
        'cliente_id',
        'cliente_emisor_id',
        'cliente_receptor_id',
        'categoria_gasto_id',
        'operador_id',
        'tasa_aplicada',
        'tasa_compra',
        'tasa_venta',
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
        'estado',
        'pagador_id',
        'asignada_at',
        'pagada_at',
        'cancelada_at',
        'motivo_cancelacion',
        'sla_notificado_en',
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
            'tasa_compra'            => 'decimal:8',
            'tasa_venta'             => 'decimal:8',
            'estado_pool'            => 'string',
            'estado'                 => 'string',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Movimiento>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('orden');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TipoOperacion>
     */
    public function tipoOperacion(): BelongsTo
    {
        return $this->belongsTo(TipoOperacion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cliente>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\CategoriaGasto>
     */
    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TasaDiaria>
     */
    public function tasaDiaria(): BelongsTo
    {
        return $this->belongsTo(TasaDiaria::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ComisionOperacion>
     */
    public function comisiones(): HasMany
    {
        return $this->hasMany(ComisionOperacion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cliente>
     */
    public function clienteEmisor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_emisor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cliente>
     */
        public function clienteReceptor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_receptor_id');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Transaccion>
     */
    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class)->orderBy('orden');
    }
}
