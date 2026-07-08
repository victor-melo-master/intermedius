<?php

namespace App\Http\Requests\Moneda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la solicitud para actualizar una moneda (PUT/PATCH /monedas/{moneda}).
 */
class UpdateMonedaRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('moneda'));
    }

    /**
     * @return array<string, mixed>
     */
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
