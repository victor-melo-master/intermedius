<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Categorías para clasificar gastos.
 *
 * @property int $id
 * @property string $nombre
 * @property int $titular_id
 * @property bool $activa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Titular|null $titular
 */
class CategoriaGasto extends Model
{
    use HasFactory;

    protected $table = 'categorias_gasto';

    protected $fillable = [
        'nombre',
        'titular_id',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Titular>
     */
    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }
}
