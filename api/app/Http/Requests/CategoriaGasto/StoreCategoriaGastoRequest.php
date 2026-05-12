<?php

namespace App\Http\Requests\CategoriaGasto;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\CategoriaGasto::class);
    }

    public function rules(): array
    {
        return [
            'nombre'     => ['required', 'string', 'max:100', 'unique:categorias_gasto,nombre'],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activa'     => ['boolean'],
        ];
    }
}
