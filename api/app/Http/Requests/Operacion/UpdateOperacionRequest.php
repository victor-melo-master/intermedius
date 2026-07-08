<?php

namespace App\Http\Requests\Operacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida la solicitud para actualizar una operación (PUT/PATCH /operaciones/{operacion}).
 */
class UpdateOperacionRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        $operacion = $this->route('operacion');

        // Solo el operador dueño, el pagador asignado, o admin/super_admin pueden editar
        $user = $this->user();
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        if ($operacion->operador_id === $user->id) {
            return true;
        }

        if ($operacion->pagador_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha'                      => ['sometimes', 'date', 'before_or_equal:today'],
            'tipo_codigo'                => ['sometimes', 'string', 'exists:tipos_operacion,codigo'],
            'cliente_id'                 => ['nullable', 'integer', 'exists:clientes,id'],
            'categoria_gasto_id'         => ['nullable', 'integer', 'exists:categorias_gasto,id'],
            'operador_id'                => ['sometimes', 'integer', 'exists:users,id'],
            'tasa_aplicada'              => ['sometimes', 'numeric', 'min:0'],
            'genera_comision'            => ['nullable', 'boolean'],
            'monto_comision'             => ['nullable', 'numeric', 'min:0'],
            'tipo_comision'              => ['nullable', 'in:pago_movil,otros_bancos,mismo_banco,manual'],
            'tasa_mercado_snapshot'      => ['nullable', 'numeric', 'min:0'],
            'fuente_tasa_mercado'        => ['nullable', 'string', 'max:30'],
            'referencia'                 => ['nullable', 'string', 'max:100'],
            'descripcion'                => ['nullable', 'string'],
            'motivo_edicion'             => ['required', 'string', 'max:500'],
            'movimientos'                => ['sometimes', 'array', 'min:2'],
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
            $operacion = $this->route('operacion');

            // No se puede editar una operación verificada (salvo super_admin)
            if ($operacion->estatus === 'verificado' && !$this->user()->hasRole('super_admin')) {
                $v->errors()->add('operacion', 'No se puede editar una operación ya verificada.');
            }

            // No se puede editar una operación cancelada
            if ($operacion->estado_pool === 'cancelada') {
                $v->errors()->add('operacion', 'No se puede editar una operación cancelada.');
            }

            // operador_id debe ser el usuario autenticado, salvo super_admin
            $user = $this->user();
            if ($this->filled('operador_id') && $user && !$user->hasRole('super_admin') && (int) $this->operador_id !== $user->id) {
                $v->errors()->add('operador_id', 'Solo un super_admin puede cambiar el operador de una operación.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo_edicion.required' => 'Debe indicar el motivo de la edición.',
            'motivo_edicion.max'      => 'El motivo no puede exceder los 500 caracteres.',
        ];
    }
}
