<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tipos de operación disponibles en el sistema.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property bool $afecta_cliente
 * @property bool $afecta_fifo
 * @property bool $genera_ganancia
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
