<?php

namespace App\Http\Requests\Configuracion;

use App\Models\ComisionOperacion;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para editar una comisión en una operación (PUT/PATCH /configuracion/comisiones-operacion/{comision_operacion}).
 */
class UpdateComisionOperacionRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'razon_edicion'         => ['required', 'string', 'min:10', 'max:500'],
            'monto'                 => ['nullable', 'numeric', 'gt:0'],
            'monto_usd_equivalente' => ['nullable', 'numeric', 'gte:0'],
            'descripcion'           => ['nullable', 'string', 'max:200'],
        ];
    }
}
