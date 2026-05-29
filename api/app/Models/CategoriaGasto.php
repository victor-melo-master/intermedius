<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function titular(): BelongsTo
    {
        return $this->belongsTo(Titular::class);
    }
}
