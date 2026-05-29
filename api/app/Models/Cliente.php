<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'alias',
        'documento',
        'telefono',
        'email',
        'notas',
        'saldo_cache_usd',
        'saldo_cache_at',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo'          => 'boolean',
            'saldo_cache_usd' => 'string',
            'saldo_cache_at'  => 'datetime',
        ];
    }
}
