<?php

namespace App\Http\Requests\Banco;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para crear un banco (POST /bancos).
 */
class StoreBancoRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Banco::class);
    }

    /**
     * @return array<string, mixed>
     */
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
