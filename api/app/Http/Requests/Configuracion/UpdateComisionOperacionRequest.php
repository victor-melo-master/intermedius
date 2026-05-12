<?php

namespace App\Http\Requests\Configuracion;

use App\Models\ComisionOperacion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComisionOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'razon_edicion'         => ['required', 'string', 'min:10', 'max:500'],
            'monto'                 => ['nullable', 'numeric', 'gt:0'],
            'monto_usd_equivalente' => ['nullable', 'numeric', 'gte:0'],
            'descripcion'           => ['nullable', 'string', 'max:200'],
        ];
    }
}
