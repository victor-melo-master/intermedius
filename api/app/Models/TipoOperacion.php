<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoOperacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_operacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'afecta_cliente',
        'afecta_fifo',
        'genera_ganancia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'afecta_cliente'  => 'boolean',
            'afecta_fifo'     => 'boolean',
            'genera_ganancia' => 'boolean',
            'activo'          => 'boolean',
        ];
    }
}
