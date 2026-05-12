<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }

    public function tipoOperacion(): BelongsTo
    {
        return $this->belongsTo(TipoOperacion::class);
    }

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
