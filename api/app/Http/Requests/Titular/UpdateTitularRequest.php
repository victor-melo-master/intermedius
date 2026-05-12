<?php

namespace App\Http\Requests\Titular;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTitularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('titular'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('titulares', 'nombre')->ignore($this->route('titular'))],
            'alias'  => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }
}
