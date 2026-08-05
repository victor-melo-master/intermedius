<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ajuste general de la aplicación (clave → valor).
 *
 * @property int $id
 * @property string $clave
 * @property string|null $valor
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Ajuste extends Model
{
    protected $table = 'ajustes';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];

    /**
     * Obtiene el valor de un ajuste, o un valor por defecto si no existe.
     *
     * @param string $clave
     * @param mixed $default
     * @return mixed
     */
    public static function obtener(string $clave, mixed $default = null): mixed
    {
        $ajuste = static::where('clave', $clave)->first();

        return $ajuste ? $ajuste->valor : $default;
    }

    /**
     * Indica si un ajuste de tipo booleano/switch está activo ('1'/'true'/'on').
     *
     * @param string $clave
     * @param bool $default Valor por defecto si el ajuste no existe
     * @return bool
     */
    public static function activo(string $clave, bool $default = false): bool
    {
        $valor = static::obtener($clave, $default);

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
