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
            'transacciones'             => ['nullable', 'array', 'min:1'],
            'transacciones.*.cuenta_origen_id'  => ['nullable', 'integer', 'exists:cuentas,id'],
            'transacciones.*.cuenta_destino_id' => ['nullable', 'integer', 'exists:cuentas,id'],
            'transacciones.*.moneda_id'         => ['required', 'integer', 'exists:monedas,id'],
            'transacciones.*.monto'             => ['required', 'numeric', 'gt:0'],
            'transacciones.*.tasa_aplicada'     => ['nullable', 'numeric', 'gt:0'],
            'transacciones.*.metodo_pago'       => ['nullable', 'string', 'max:50'],
            'transacciones.*.comprobante'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'transacciones.*.cuenta_origen_id.exists' => 'La cuenta de origen no existe.',
            'transacciones.*.cuenta_destino_id.exists' => 'La cuenta de destino no existe.',
            'transacciones.*.moneda_id.required' => 'Cada transacción debe tener una moneda.',
            'transacciones.*.monto.required' => 'Cada transacción debe tener un monto.',
            'transacciones.*.monto.gt' => 'El monto de cada transacción debe ser mayor a 0.',
        ];
    }
}
