<?php

namespace App\Http\Requests\Moneda;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonedaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Moneda::class);
    }

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
