<?php

namespace App\Http\Requests\Titular;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para crear un titular (POST /titulares).
 */
class StoreTitularRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Titular::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:titulares,nombre'],
            'alias'  => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }
}
