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
 * Comisiones definidas por cuenta o banco.
 *
 * @property int $id
 * @property int|null $cuenta_id
 * @property int|null $banco_id
 * @property string|null $descripcion
 * @property string $tipo_calculo
 * @property string $valor
 * @property int $moneda_id
 * @property string|null $aplica_a
 * @property \Illuminate\Support\Carbon $vigente_desde
 * @property \Illuminate\Support\Carbon|null $vigente_hasta
 * @property bool $activa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Cuenta|null $cuenta
 * @property-read \App\Models\Banco|null $banco
 * @property-read \App\Models\Moneda|null $moneda
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static vigentes(\Illuminate\Support\Carbon|string $fecha)
 */
class ComisionCuenta extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'comisiones_cuenta';

    protected $fillable = [
        'cuenta_id',
        'banco_id',
        'descripcion',
        'tipo_calculo',
        'valor',
        'moneda_id',
        'aplica_a',
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
            ->setDescriptionForEvent(fn (string $e) => "ComisionCuenta {$e}");
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->cuenta_id === null && $model->banco_id === null) {
                throw new \InvalidArgumentException('ComisionCuenta requiere al menos cuenta_id o banco_id.');
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Cuenta>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
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
