<?php

namespace App\Http\Requests\Moneda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para crear una moneda (POST /monedas).
 */
class StoreMonedaRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Moneda::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo'    => ['required', 'string', 'max:10', 'unique:monedas,codigo'],
            'nombre'    => ['required', 'string', 'max:100'],
            'simbolo'   => ['nullable', 'string', 'max:10'],
            'es_fiat'   => ['boolean'],
            'es_cripto' => ['boolean'],
            'decimales' => ['integer', 'min:0', 'max:18'],
            'activa'    => ['boolean'],
        ];
    }
}
