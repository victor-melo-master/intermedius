<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'operacion_id'      => $this->operacion_id,
            'cuenta_origen_id'  => $this->cuenta_origen_id,
            'cuenta_destino_id' => $this->cuenta_destino_id,
            'moneda_id'         => $this->moneda_id,
            'monto'             => (string) $this->monto,
            'tasa_aplicada'     => $this->tasa_aplicada ? (string) $this->tasa_aplicada : null,
            'tasas_snapshot'    => $this->tasas_snapshot,
            'metodo_pago'       => $this->metodo_pago,
            'comprobante'       => $this->comprobante,
            'estado'            => $this->estado,
            'motivo_rechazo'    => $this->motivo_rechazo,
            'confirmada_en'     => $this->confirmada_en?->toIso8601String(),
            'orden'             => $this->orden,

            'moneda'            => $this->whenLoaded('moneda', fn () => [
                'id'     => $this->moneda->id,
                'codigo' => $this->moneda->codigo,
                'nombre' => $this->moneda->nombre,
                'simbolo' => $this->moneda->simbolo,
            ]),
            'cuenta_origen'     => $this->whenLoaded('cuentaOrigen', fn () => [
                'id'          => $this->cuentaOrigen->id,
                'alias'       => $this->cuentaOrigen->alias,
                'nombre'      => $this->cuentaOrigen->nombre,
                'titular_id'  => $this->cuentaOrigen->titular_id,
                'saldo_cache' => $this->cuentaOrigen->saldo_cache,
                'moneda'      => $this->cuentaOrigen->relationLoaded('moneda') ? [
                    'id'     => $this->cuentaOrigen->moneda->id,
                    'codigo' => $this->cuentaOrigen->moneda->codigo,
                    'simbolo' => $this->cuentaOrigen->moneda->simbolo,
                ] : null,
            ]),
            'cuenta_destino'    => $this->whenLoaded('cuentaDestino', fn () => [
                'id'          => $this->cuentaDestino->id,
                'alias'       => $this->cuentaDestino->alias,
                'nombre'      => $this->cuentaDestino->nombre,
                'titular_id'  => $this->cuentaDestino->titular_id,
                'saldo_cache' => $this->cuentaDestino->saldo_cache,
                'moneda'      => $this->cuentaDestino->relationLoaded('moneda') ? [
                    'id'     => $this->cuentaDestino->moneda->id,
                    'codigo' => $this->cuentaDestino->moneda->codigo,
                    'simbolo' => $this->cuentaDestino->moneda->simbolo,
                ] : null,
            ]),
        ];
    }
}
