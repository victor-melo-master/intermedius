<?php

namespace App\Http\Requests\CategoriaGasto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('categoria_gasto'));
    }

    public function rules(): array
    {
        return [
            'nombre'     => ['sometimes', 'string', 'max:100', Rule::unique('categorias_gasto', 'nombre')->ignore($this->route('categoria_gasto'))],
            'titular_id' => ['nullable', 'integer', 'exists:titulares,id'],
            'activa'     => ['boolean'],
        ];
    }
}
