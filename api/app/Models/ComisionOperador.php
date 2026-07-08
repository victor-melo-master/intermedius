<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Comisiones configuradas por operador (titular) según tipo de operación.
 *
 * @property int $id
 * @property int $titular_id
 * @property int $tipo_operacion_id
 * @property string|null $descripcion
 * @property string $tipo_calculo
 * @property string $valor
 * @property int $moneda_id
 * @property string $base_calculo
 * @property \Illuminate\Support\Carbon $vigente_desde
 * @property \Illuminate\Support\Carbon|null $vigente_hasta
 * @property bool $activa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Titular|null $titular
 * @property-read \App\Models\TipoOperacion|null $tipoOperacion
 * @property-read \App\Models\Moneda|null $moneda
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static vigentes(\Illuminate\Support\Carbon|string $fecha)
 */
class ComisionOperador extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'comisiones_operador';

    protected $fillable = [
        'titular_id',
        'tipo_operacion_id',
        'descripcion',
        'tipo_calculo',
        'valor',
        'moneda_id',
        'base_calculo',
        'vigente_desde',
        'vigente_hasta',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'valor'         => 'decimal:8',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'activa'        => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['valor', 'tipo_calculo', 'base_calculo', 'activa', 'vigente_desde', 'vigente_hasta'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "ComisionOperador {$e}");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Titular>
     */
    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TipoOperacion>
     */
    public function tipoOperacion(): BelongsTo
    {
        return $this->belongsTo(TipoOperacion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function scopeVigentes(Builder $query, Carbon|string $fecha): Builder
    {
        $fecha = is_string($fecha) ? Carbon::parse($fecha) : $fecha;

        return $query
            ->where('vigente_desde', '<=', $fecha)
            ->where(fn (Builder $q) => $q
                ->whereNull('vigente_hasta')
                ->orWhere('vigente_hasta', '>=', $fecha)
            );
    }
}
