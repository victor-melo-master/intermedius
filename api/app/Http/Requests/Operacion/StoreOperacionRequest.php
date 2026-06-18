<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Operacion::class);
    }

    public function rules(): array
    {
        return [
            'fecha'                      => ['required', 'date', 'before_or_equal:today'],
            'tipo_codigo'                => ['required', 'string', 'exists:tipos_operacion,codigo'],
            'cliente_id'                 => ['nullable', 'integer', 'exists:clientes,id'],
            'categoria_gasto_id'         => ['nullable', 'integer', 'exists:categorias_gasto,id'],
            'operador_id'                => ['required', 'integer', 'exists:users,id'],
            'tasa_aplicada'              => ['nullable', 'numeric', 'min:0'],
            'genera_comision'            => ['nullable', 'boolean'],
            'monto_comision'             => ['nullable', 'numeric', 'min:0'],
            'tipo_comision'              => ['nullable', 'in:pago_movil,otros_bancos,mismo_banco,manual'],
            'tasa_mercado_snapshot'      => ['nullable', 'numeric', 'min:0'],
            'fuente_tasa_mercado'        => ['nullable', 'string', 'max:30'],
            'referencia'                 => ['nullable', 'string', 'max:100'],
            'descripcion'                => ['nullable', 'string'],
            'origen'                     => ['nullable', 'in:manual,importado,ajuste_apertura'],
            'origen_referencia'          => ['nullable', 'string', 'max:100', 'unique:operaciones,origen_referencia'],
            'movimientos'                => ['required', 'array', 'min:1'],
            'movimientos.*.cuenta_id'    => ['required', 'integer', 'exists:cuentas,id'],
            'movimientos.*.monto'        => ['required', 'numeric', 'not_in:0'],
            'movimientos.*.tasa_a_usd'   => ['nullable', 'numeric', 'gt:0'],
        ];
    }

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
        });
    }
}
