<?php

namespace App\Http\Requests\Moneda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonedaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('moneda'));
    }

    public function rules(): array
    {
        return [
            'codigo'    => ['sometimes', 'string', 'max:10', Rule::unique('monedas', 'codigo')->ignore($this->route('moneda'))],
            'nombre'    => ['sometimes', 'string', 'max:100'],
            'simbolo'   => ['nullable', 'string', 'max:10'],
            'es_fiat'   => ['boolean'],
            'es_cripto' => ['boolean'],
            'decimales' => ['integer', 'min:0', 'max:18'],
            'activa'    => ['boolean'],
        ];
    }
}
