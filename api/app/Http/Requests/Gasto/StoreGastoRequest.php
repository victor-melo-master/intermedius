<?php

namespace App\Http\Requests\Gasto;

use App\Models\Operacion;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para registrar un gasto (POST /gastos).
 */
class StoreGastoRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Operacion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha'                      => ['required', 'date', 'before_or_equal:today'],
            'categoria_gasto_id'         => ['required', 'integer', 'exists:categorias_gasto,id'],
            'operador_id'                => ['required', 'integer', 'exists:users,id'],
            'referencia'                 => ['nullable', 'string', 'max:100'],
            'descripcion'                => ['nullable', 'string'],
            'movimientos'                => ['required', 'array', 'min:1'],
            'movimientos.*.cuenta_id'    => ['required', 'integer', 'exists:cuentas,id'],
            'movimientos.*.monto'        => ['required', 'numeric', 'not_in:0'],
            'movimientos.*.tasa_a_usd'   => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Inyecta tipo_codigo='gasto' antes de pasar al servicio.
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if (is_array($data) && $key === null) {
            $data['tipo_codigo'] = 'gasto';
            $data['origen']      = $data['origen'] ?? 'manual';
        }

        return $data;
    }
}
