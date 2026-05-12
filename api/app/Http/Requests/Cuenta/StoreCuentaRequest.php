<?php

namespace App\Http\Requests\Cuenta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCuentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Cuenta::class);
    }

    public function rules(): array
    {
        return [
            'titular_id'     => ['required', 'integer', 'exists:titulares,id'],
            'banco_id'       => ['nullable', 'integer', 'exists:bancos,id'],
            'moneda_id'      => ['required', 'integer', 'exists:monedas,id'],
            'alias'          => [
                'required', 'string', 'max:100',
                Rule::unique('cuentas')->where(fn ($q) => $q->where('titular_id', $this->titular_id)),
            ],
            'tipo'           => ['required', Rule::in(['banco', 'plataforma', 'cash', 'wallet'])],
            'numero_cuenta'  => ['nullable', 'string', 'max:50'],
            'activa'         => ['boolean'],
            'notas'          => ['nullable', 'string'],
        ];
    }
}
