<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Comisiones aplicadas a una operación específica.
 *
 * @property int $id
 * @property int $operacion_id
 * @property string $tipo
 * @property string|null $origen_type
 * @property int|null $origen_id
 * @property string|null $descripcion
 * @property string $monto
 * @property int $moneda_id
 * @property string $monto_usd_equivalente
 * @property int|null $movimiento_id
 * @property int|null $editada_por_id
 * @property \Illuminate\Support\Carbon|null $editada_at
 * @property string|null $razon_edicion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Operacion|null $operacion
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $origen
 * @property-read \App\Models\Moneda|null $moneda
 * @property-read \App\Models\Movimiento|null $movimiento
 * @property-read \App\Models\User|null $editadaPor
 */
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
            'monto'                 => 'decimal:2',
            'monto_usd_equivalente' => 'decimal:2',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Operacion>
     */
    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model>
     */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Moneda>
     */
    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Movimiento>
     */
    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function editadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editada_por_id');
    }
}
