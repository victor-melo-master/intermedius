<?php

namespace App\Http\Requests\CategoriaGasto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la solicitud para actualizar una categoría de gasto (PUT/PATCH /categorias-gasto/{categoria_gasto}).
 */
class UpdateCategoriaGastoRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('categoria_gasto'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre'     => ['sometimes', 'string', 'max:100', Rule::unique('categorias_gasto', 'nombre')->ignore($this->route('categoria_gasto'))],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activa'     => ['boolean'],
        ];
    }
}
