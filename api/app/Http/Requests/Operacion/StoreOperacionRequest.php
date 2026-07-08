<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// src/Http/Requests/Operacion/StoreOperacionRequest.php
/**
 * Valida la solicitud para crear una operación (POST /operaciones).
 */
class StoreOperacionRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Operacion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha'                      => ['required', 'date', 'before_or_equal:today'],
            'tipo_codigo'                => ['required', 'string', 'exists:tipos_operacion,codigo'],
            'cliente_id'                 => ['nullable', 'integer', 'exists:clientes,id'],
            'cliente_emisor_id'          => ['nullable', 'integer', 'exists:clientes,id', 'required_if:tipo_codigo,intermediada'],
            'cliente_receptor_id'        => ['nullable', 'integer', 'exists:clientes,id', 'required_if:tipo_codigo,intermediada'],
            'categoria_gasto_id'         => ['nullable', 'integer', 'exists:categorias_gasto,id'],
            'operador_id'                => ['required', 'integer', 'exists:users,id'],
            'tasa_aplicada'              => ['nullable', 'numeric', 'min:0'],
            'tasa_compra'                => ['nullable', 'numeric', 'min:0', 'required_if:tipo_codigo,intermediada'],
            'tasa_venta'                 => ['nullable', 'numeric', 'min:0', 'required_if:tipo_codigo,intermediada'],
            'genera_comision'            => ['nullable', 'boolean'],
            'monto_comision'             => ['nullable', 'numeric', 'min:0'],
            'tipo_comision'              => ['nullable', 'in:pago_movil,otros_bancos,mismo_banco,manual'],
            'tasa_mercado_snapshot'      => ['nullable', 'numeric', 'min:0'],
            'fuente_tasa_mercado'        => ['nullable', 'string', 'max:30'],
            'referencia'                 => ['nullable', 'string', 'max:100'],
            'descripcion'                => ['nullable', 'string'],
            'origen'                     => ['nullable', 'in:manual,importado,ajuste_apertura'],
            'origen_referencia'          => ['nullable', 'string', 'max:100', 'unique:operaciones,origen_referencia'],
            'movimientos' => ['required', 'array', function ($attribute, $value, $fail) {
                if ($this->tipo_codigo === 'intermediada' && count($value) < 4) {
                    $fail('La operación intermediada requiere al menos 4 movimientos (2 cuentas emisor + 2 cuentas receptor).');
                }
                if ($this->tipo_codigo !== 'intermediada' && count($value) < 2) {
                    $fail('La operación requiere al menos 2 movimientos.');
                }
            }],
            'movimientos.*.cuenta_id'    => ['required', 'integer', 'exists:cuentas,id'],
            'movimientos.*.monto'        => ['required', 'numeric', 'not_in:0'],
            'movimientos.*.tasa_a_usd'   => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // categoria_gasto_id requerido si tipo es 'gasto'
            if ($this->tipo_codigo === 'gasto' && empty($this->categoria_gasto_id)) {
                $v->errors()->add('categoria_gasto_id', 'El campo categoría de gasto es obligatorio para operaciones de tipo gasto.');
            }

            // operador_id debe ser el usuario autenticado, salvo super_admin
            $user = $this->user();
            if ($user && ! $user->hasRole('super_admin') && (int) $this->operador_id !== $user->id) {
                $v->errors()->add('operador_id', 'Solo un super_admin puede registrar operaciones a nombre de otro usuario.');
            }

            // Para intermediada: tasa_venta debe ser mayor que tasa_compra
            if ($this->tipo_codigo === 'intermediada') {
                if ((float) $this->tasa_venta <= (float) $this->tasa_compra) {
                    $v->errors()->add('tasa_venta', 'La tasa de venta debe ser mayor que la tasa de compra.');
                }
                if ($this->cliente_emisor_id === $this->cliente_receptor_id) {
                    $v->errors()->add('cliente_receptor_id', 'El cliente emisor y receptor no pueden ser el mismo.');
                }
            }
        });
    }
}
