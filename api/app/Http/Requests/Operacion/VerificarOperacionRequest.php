<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;

// src/Http/Requests/Operacion/VerificarOperacionRequest.php
class VerificarOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('verificar', $this->route('operacion'));
    }

    public function rules(): array
    {
        return [];
    }
}
