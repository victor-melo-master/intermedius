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
 * Comisiones definidas por método de pago.
 *
 * @property int $id
 * @property string $nombre_metodo
 * @property int $cuenta_id
 * @property string|null $descripcion
 * @property string $tipo_calculo
 * @property string $valor
 * @property int $moneda_id
 * @property \Illuminate\Support\Carbon $vigente_desde
 * @property \Illuminate\Support\Carbon|null $vigente_hasta
 * @property bool $activa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Cuenta|null $cuenta
 * @property-read \App\Models\Moneda|null $moneda
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static vigentes(\Illuminate\Support\Carbon|string $fecha)
 */
class ComisionMetodoPago extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'comisiones_metodo_pago';

    protected $fillable = [
        'nombre_metodo',
        'cuenta_id',
        'descripcion',
        'tipo_calculo',
        'valor',
        'moneda_id',
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
            ->logOnly(['valor', 'tipo_calculo', 'activa', 'vigente_desde', 'vigente_hasta'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "ComisionMetodoPago {$e}");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cuenta>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
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
