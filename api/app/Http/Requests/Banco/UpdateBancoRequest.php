<?php

namespace App\Http\Requests\Banco;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBancoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('banco'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('bancos', 'nombre')->ignore($this->route('banco'))],
            'codigo' => ['nullable', 'string', 'max:20'],
            'pais'   => ['nullable', 'string', 'size:2'],
            'activo' => ['boolean'],
        ];
    }
}
