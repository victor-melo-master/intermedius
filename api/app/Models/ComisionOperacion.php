<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ComisionOperacion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'comisiones_operacion';

    protected $fillable = [
        'operacion_id',
        'tipo',
        'origen_type',
        'origen_id',
        'descripcion',
        'monto',
        'moneda_id',
        'monto_usd_equivalente',
        'movimiento_id',
        'editada_por_id',
        'editada_at',
        'razon_edicion',
    ];

    protected function casts(): array
    {
        return [
            'monto'                 => 'decimal:4',
            'monto_usd_equivalente' => 'decimal:4',
            'editada_at'            => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['monto', 'monto_usd_equivalente', 'descripcion', 'razon_edicion', 'editada_por_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "ComisionOperacion {$e}");
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    public function editadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editada_por_id');
    }
}
