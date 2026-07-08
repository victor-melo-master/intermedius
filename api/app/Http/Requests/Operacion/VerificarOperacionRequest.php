<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;

// src/Http/Requests/Operacion/VerificarOperacionRequest.php
/**
 * Valida la solicitud para verificar una operación (POST /operaciones/{operacion}/verificar).
 */
class VerificarOperacionRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('verificar', $this->route('operacion'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
