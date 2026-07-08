<?php

namespace App\Http\Requests\CategoriaGasto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para crear una categoría de gasto (POST /categorias-gasto).
 */
class StoreCategoriaGastoRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\CategoriaGasto::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre'     => ['required', 'string', 'max:100', 'unique:categorias_gasto,nombre'],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activa'     => ['boolean'],
        ];
    }
}
