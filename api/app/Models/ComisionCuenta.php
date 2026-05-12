<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
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
