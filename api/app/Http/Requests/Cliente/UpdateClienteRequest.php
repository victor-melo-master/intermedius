<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud para actualizar un cliente (PUT/PATCH /clientes/{cliente}).
 */
class UpdateClienteRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cliente'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre'    => ['sometimes', 'string', 'max:255'],
            'alias'     => ['nullable', 'string', 'max:100'],
            'documento' => ['nullable', 'string', 'max:50'],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:255'],
            'notas'     => ['nullable', 'string'],
            'activo'    => ['boolean'],
        ];
    }
}
