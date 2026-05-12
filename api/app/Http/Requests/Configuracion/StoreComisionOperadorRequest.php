<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreComisionOperadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'titular_id'        => ['required', 'integer', 'exists:titulares,id'],
            'tipo_operacion_id' => ['nullable', 'integer', 'exists:tipos_operacion,id'],
            'descripcion'       => ['required', 'string', 'max:100'],
            'tipo_calculo'      => ['required', 'in:porcentaje,monto_fijo'],
            'valor'             => ['required', 'numeric', 'gt:0'],
            'moneda_id'         => ['required', 'integer', 'exists:monedas,id'],
            'base_calculo'      => ['sometimes', 'in:monto_operacion,ganancia_bruta'],
            'vigente_desde'     => ['required', 'date'],
            'vigente_hasta'     => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'activa'            => ['sometimes', 'boolean'],
        ];
    }
}
