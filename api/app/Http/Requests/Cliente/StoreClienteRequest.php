<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
/**
 * Valida la solicitud para crear un cliente (POST /clientes).
 */
class StoreClienteRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Cliente::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre'    => ['required', 'string', 'max:255'],
            'alias'     => ['nullable', 'string', 'max:100'],
            'documento' => ['nullable', 'string', 'max:50'],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:255'],
            'notas'     => ['nullable', 'string'],
            'activo'    => ['boolean'],
        ];
    }
}
