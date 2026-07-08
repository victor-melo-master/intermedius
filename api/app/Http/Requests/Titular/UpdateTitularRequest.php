<?php

namespace App\Http\Requests\Titular;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la solicitud para actualizar un titular (PUT/PATCH /titulares/{titular}).
 */
class UpdateTitularRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('titular'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('titulares', 'nombre')->ignore($this->route('titular'))],
            'alias'  => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }
}
