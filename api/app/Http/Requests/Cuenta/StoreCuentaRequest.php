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

    protected function prepareForValidation(): void
    {
        if ($this->tipo === 'efectivo' && !$this->filled('titular_id') && !$this->filled('cliente_id')) {
            $this->merge(['titular_id' => 1]);
        }
    }

    public function rules(): array
    {
        $titularId = $this->input('titular_id');
        $clienteId = $this->input('cliente_id');
        $ownerId   = $titularId ?: $clienteId;

        return [
            'titular_id'     => ['nullable', 'integer', 'exists:titulares,id', 'required_without:cliente_id'],
            'cliente_id'     => ['nullable', 'integer', 'exists:clientes,id', 'required_without:titular_id'],
            'banco_id'       => ['nullable', 'integer', 'exists:bancos,id'],
            'moneda_id'      => ['required', 'integer', 'exists:monedas,id'],
            'alias'          => [
                'required', 'string', 'max:100',
                Rule::unique('cuentas')
                    ->where(fn ($q) => $ownerId
                        ? $q->where(function ($sq) use ($titularId, $clienteId) {
                            $sq->where('titular_id', $titularId)->orWhere('cliente_id', $clienteId);
                        })
                        : $q
                    ),
            ],
            'tipo'           => ['required', Rule::in(['banco', 'plataforma', 'cash', 'wallet', 'zelle', 'efectivo', 'otro'])],
            'numero_cuenta'  => ['nullable', 'string', 'max:50'],
            'activa'         => ['boolean'],
            'notas'          => ['nullable', 'string'],
        ];
    }
}
