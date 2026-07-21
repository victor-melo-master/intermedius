<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Operacion::class);
    }

    public function rules(): array
    {
        return [
            'fecha'            => ['required', 'date', 'before_or_equal:today'],
            'tipo_codigo'      => ['required', 'string', 'exists:tipos_operacion,codigo'],
            'moneda_codigo'    => ['required', 'string', 'exists:monedas,codigo'],
            'cliente_id'       => ['nullable', 'integer', 'exists:clientes,id'],
            'operador_id'      => ['required', 'integer', 'exists:users,id'],
            'tasa_aplicada'    => ['required', 'numeric', 'min:0.01'],
            'monto_solicitado' => ['required', 'numeric', 'min:0.01'],
            'descripcion'      => ['nullable', 'string'],
        ];
    }
}
