<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para crear una comisión por cuenta (POST /configuracion/comisiones-cuenta).
 */
class StoreComisionCuentaRequest extends FormRequest
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
            'cuenta_id'     => ['nullable', 'integer', 'exists:cuentas,id'],
            'banco_id'      => ['nullable', 'integer', 'exists:bancos,id'],
            'descripcion'   => ['required', 'string', 'max:100'],
            'tipo_calculo'  => ['required', 'in:porcentaje,monto_fijo'],
            'valor'         => ['required', 'numeric', 'gt:0'],
            'moneda_id'     => ['required', 'integer', 'exists:monedas,id'],
            'aplica_a'      => ['required', 'in:ingreso,egreso,ambos'],
            'vigente_desde' => ['required', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'activa'        => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (empty($this->input('cuenta_id')) && empty($this->input('banco_id'))) {
                $v->errors()->add('cuenta_id', 'Debe especificar al menos cuenta_id o banco_id.');
            }
        });
    }
}
