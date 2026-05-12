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

class TasaDiaria extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tasas_diarias';

    protected $fillable = [
        'fecha',
        'moneda_base_id',
        'moneda_cotizada_id',
        'tasa_compra',
        'tasa_venta',
        'definida_por_id',
        'notas',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'fecha'         => 'date',
            'tasa_compra'   => 'decimal:8',
            'tasa_venta'    => 'decimal:8',
            'vigente_desde' => 'datetime',
            'vigente_hasta' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tasa_compra', 'tasa_venta', 'vigente_desde', 'vigente_hasta'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "TasaDiaria {$eventName}");
    }

    public function monedaBase(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_base_id');
    }

    public function monedaCotizada(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'moneda_cotizada_id');
    }

    public function definidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'definida_por_id');
    }

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
