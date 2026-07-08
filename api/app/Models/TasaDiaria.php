<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Tasas de cambio diarias configuradas manualmente para el sistema.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $fecha
 * @property int $moneda_base_id
 * @property int $moneda_cotizada_id
 * @property string $tasa_compra
 * @property string|null $tasa_compra_minima
 * @property string $tasa_venta
 * @property string|null $tasa_venta_minima
 * @property int|null $definida_por_id
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $vigente_desde
 * @property \Illuminate\Support\Carbon|null $vigente_hasta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Moneda|null $monedaBase
 * @property-read \App\Models\Moneda|null $monedaCotizada
 * @property-read \App\Models\User|null $definidaPor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Operacion> $operaciones
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static vigentes(\Illuminate\Support\Carbon|null $momento = null)
 */
class TasaDiaria extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tasas_diarias';

    protected $fillable = [
        'fecha',
        'moneda_base_id',
        'moneda_cotizada_id',
        'tasa_compra',
        'tasa_compra_minima',
        'tasa_venta',
        'tasa_venta_minima',
        'definida_por_id',
        'notas',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'fecha'               => 'date',
            'tasa_compra'         => 'decimal:8',
            'tasa_compra_minima'  => 'decimal:8',
            'tasa_venta'          => 'decimal:8',
            'tasa_venta_minima'   => 'decimal:8',
            'vigente_desde'       => 'datetime',
            'vigente_hasta'       => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tasa_compra', 'tasa_compra_minima', 'tasa_venta', 'tasa_venta_minima', 'vigente_desde', 'vigente_hasta'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "TasaDiaria {$eventName}");
    }

    /**
     * Indica si una tasa efectiva es desfavorable para la casa respecto al mínimo configurado.
     *
     * - Compra : desfavorable si tasaEfectiva > tasa_compra_minima (la casa paga más del mínimo permitido).
     * - Venta  : desfavorable si tasaEfectiva < tasa_venta_minima  (la casa recibe menos del mínimo permitido).
     * - Si el mínimo correspondiente es null (sin límite configurado): retorna false.
     *
     * @param  string $direccion  'compra' | 'venta'
     */
    public function esDesfavorableParaLaCasa(float $tasaEfectiva, string $direccion): bool
    {
        return match ($direccion) {
            'compra' => $this->tasa_compra_minima !== null
                && $tasaEfectiva > (float) $this->tasa_compra_minima,
            'venta'  => $this->tasa_venta_minima !== null
                && $tasaEfectiva < (float) $this->tasa_venta_minima,
            default  => throw new \InvalidArgumentException("Dirección debe ser 'compra' o 'venta'."),
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function monedaBase(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_base_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function monedaCotizada(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_cotizada_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function definidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'definida_por_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Operacion>
     */
    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class);
    }

    /**
     * Scope: tasas vigentes en el momento dado.
     * vigente_desde <= $momento AND (vigente_hasta IS NULL OR vigente_hasta > $momento)
     */
    public function scopeVigentes(Builder $query, ?Carbon $momento = null): Builder
    {
        $momento ??= now();

        return $query
            ->where('vigente_desde', '<=', $momento)
            ->where(fn (Builder $q) => $q
                ->whereNull('vigente_hasta')
                ->orWhere('vigente_hasta', '>', $momento)
            );
    }
}
