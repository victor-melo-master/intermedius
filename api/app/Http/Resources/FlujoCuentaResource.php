<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlujoCuentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'tipo'          => $this->tipo,
            'monto'         => $this->monto,
            'moneda'        => $this->whenLoaded('moneda'),
            'descripcion'   => $this->descripcion,
            'operacion_id'  => $this->operacion_id,
            'transaccion'   => $this->whenLoaded('transaccion'),
            'registrado_por' => $this->whenLoaded('registradoPor'),
            'created_at'    => $this->created_at,
        ];
    }
}
