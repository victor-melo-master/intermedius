<?php

namespace App\Http\Requests\Cuenta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCuentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cuenta'));
    }

    public function rules(): array
    {
        $cuenta = $this->route('cuenta');

        return [
            'banco_id'      => ['nullable', 'integer', 'exists:bancos,id'],
            'moneda_id'     => ['sometimes', 'integer', 'exists:monedas,id'],
            'alias'         => [
                'sometimes', 'string', 'max:100',
                Rule::unique('cuentas')
                    ->where(fn ($q) => $q->where('titular_id', $cuenta->titular_id))
                    ->ignore($cuenta->id),
            ],
            'tipo'          => ['sometimes', Rule::in(['banco', 'plataforma', 'cash', 'wallet'])],
            'numero_cuenta' => ['nullable', 'string', 'max:50'],
            'activa'        => ['boolean'],
            'notas'         => ['nullable', 'string'],
        ];
    }
}
