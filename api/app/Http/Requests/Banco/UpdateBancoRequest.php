<?php

namespace App\Http\Requests\Banco;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la solicitud para actualizar un banco (PUT/PATCH /bancos/{banco}).
 */
class UpdateBancoRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('banco'));
    }

    /**
     * @return array<string, mixed>
     */
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
