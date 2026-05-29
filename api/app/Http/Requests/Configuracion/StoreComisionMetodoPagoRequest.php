<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreComisionMetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'nombre_metodo' => ['required', 'string', 'max:80'],
            'cuenta_id'     => ['nullable', 'integer', 'exists:cuentas,id'],
            'descripcion'   => ['required', 'string', 'max:100'],
            'tipo_calculo'  => ['required', 'in:porcentaje,monto_fijo'],
            'valor'         => ['required', 'numeric', 'gt:0'],
            'moneda_id'     => ['required', 'integer', 'exists:monedas,id'],
            'vigente_desde' => ['required', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'activa'        => ['sometimes', 'boolean'],
        ];
    }
}
