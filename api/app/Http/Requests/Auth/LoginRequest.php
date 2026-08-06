<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud de inicio de sesión (POST /auth/login).
 */
class LoginRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 'login' acepta email o username (campo `name` del usuario).
            // 'email' se mantiene por compatibilidad con clientes previos.
            'login'    => ['required_without:email', 'string'],
            'email'    => ['required_without:login', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
