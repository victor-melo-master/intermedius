<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreTasaDiariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'fecha'              => ['required', 'date'],
            'moneda_base_id'     => ['required', 'integer', 'exists:monedas,id'],
            'moneda_cotizada_id' => ['required', 'integer', 'exists:monedas,id', 'different:moneda_base_id'],
            'tasa_compra'        => ['required', 'numeric', 'gt:0'],
            'tasa_compra_minima' => ['nullable', 'numeric', 'gt:0'],
            'tasa_venta'         => ['required', 'numeric', 'gt:0'],
            'tasa_venta_minima'  => ['nullable', 'numeric', 'gt:0'],
            'notas'              => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $tasaVenta  = (float) $this->input('tasa_venta', 0);
            $tasaCompra = (float) $this->input('tasa_compra', 0);
            $notas      = $this->input('notas', '');

            if ($tasaVenta < $tasaCompra) {
                if (empty($notas) || mb_strlen(trim($notas)) < 10) {
                    $v->errors()->add(
                        'tasa_venta',
                        'La tasa de venta es menor que la de compra. Justifica la excepción en el campo notas (mínimo 10 caracteres).'
                    );
                }
            }
        });
    }
}
