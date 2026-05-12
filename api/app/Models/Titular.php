<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Titular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'titulares';

    protected $fillable = [
        'nombre',
        'alias',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categoriasGasto(): HasMany
    {
        return $this->hasMany(CategoriaGasto::class);
    }
}
