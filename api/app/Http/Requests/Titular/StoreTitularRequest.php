<?php

namespace App\Http\Requests\Titular;

use Illuminate\Foundation\Http\FormRequest;

class StoreTitularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Titular::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:titulares,nombre'],
            'alias'  => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }
}
