<?php

namespace App\Http\Requests\Banco;

use Illuminate\Foundation\Http\FormRequest;

class StoreBancoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Banco::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:bancos,nombre'],
            'codigo' => ['nullable', 'string', 'max:20'],
            'pais'   => ['nullable', 'string', 'size:2'],
            'activo' => ['boolean'],
        ];
    }
}
