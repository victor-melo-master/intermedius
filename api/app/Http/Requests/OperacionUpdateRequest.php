<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class OperacionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operacion = $this->route('operacion');

        if ($operacion && $operacion->estado !== 'en_espera') {
            throw ValidationException::withMessages([
                'estado' => 'No se puede editar una operación que no está en espera.',
            ]);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'fecha'               => 'sometimes|date',
            'tipo_operacion_id'   => 'sometimes|exists:tipos_operacion,id',
            'cliente_id'          => 'nullable|exists:clientes,id',
            'cliente_emisor_id'   => 'nullable|exists:clientes,id',
            'cliente_receptor_id' => 'nullable|exists:clientes,id',
            'categoria_gasto_id'  => 'nullable|exists:categorias_gasto,id',
            'tasa_aplicada'       => 'nullable|numeric|min:0',
            'tasa_compra'         => 'nullable|numeric|min:0',
            'tasa_venta'          => 'nullable|numeric|min:0',
            'referencia'          => 'nullable|string|max:100',
            'descripcion'         => 'nullable|string',
        ];
    }
}
