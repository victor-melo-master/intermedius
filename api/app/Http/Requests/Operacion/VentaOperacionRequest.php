<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VentaOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Operacion::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fecha'       => $this->fecha ?? now()->toDateString(),
            'tipo_codigo' => $this->tipo_codigo ?? 'venta_usd',
            'operador_id' => $this->operador_id ?? $this->user()?->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'fecha'                      => ['required', 'date', 'before_or_equal:today'],
            'tipo_codigo'                => ['required', 'string', 'in:venta_usd'],
            'moneda_codigo'              => ['required', 'string', 'exists:monedas,codigo'],
            'cliente_id'                 => ['required', 'integer', 'exists:clientes,id'],
            'operador_id'                => ['required', 'integer', 'exists:users,id'],
            'tasa_aplicada'              => ['required', 'numeric', 'min:0.01'],
            'monto_solicitado'           => ['required', 'numeric', 'min:0.01'],
            'tasa_mercado_snapshot'      => ['nullable', 'numeric', 'min:0'],
            'fuente_tasa_mercado'        => ['nullable', 'string', 'max:30'],
            'descripcion'                => ['nullable', 'string'],
            'referencia'                 => ['nullable', 'string', 'max:100'],
            'origen'                     => ['nullable', 'in:manual,importado'],

            'transacciones'                       => ['required', 'array', 'min:2'],
            'transacciones.*.cuenta_origen_id'    => ['nullable', 'integer', 'exists:cuentas,id'],
            'transacciones.*.cuenta_destino_id'   => ['nullable', 'integer', 'exists:cuentas,id'],
            'transacciones.*.moneda_id'           => ['required', 'integer', 'exists:monedas,id'],
            'transacciones.*.monto'               => ['required', 'numeric', 'gt:0'],
            'transacciones.*.cliente_id'          => ['nullable', 'integer', 'exists:clientes,id'],
            'transacciones.*.metodo_pago'         => ['nullable', 'string', 'max:50'],
            'transacciones.*.comprobante'         => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $user = $this->user();
            if ($user && ! $user->hasRole('super_admin') && (int) $this->operador_id !== $user->id) {
                $v->errors()->add('operador_id', 'Solo un super_admin puede registrar ventas a nombre de otro usuario.');
            }

            if (empty($this->tasa_mercado_snapshot)) {
                $v->errors()->add('tasa_mercado_snapshot', 'La tasa de mercado es obligatoria para cerrar la venta.');
            }
        });
    }
}
