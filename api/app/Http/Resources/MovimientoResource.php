<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'operacion_id'          => $this->operacion_id,
            'cuenta_id'             => $this->cuenta_id,
            'moneda_id'             => $this->moneda_id,
            'monto'                 => (string) $this->monto,
            'tasa_a_usd'            => (string) $this->tasa_a_usd,
            'monto_usd_equivalente' => (string) $this->monto_usd_equivalente,
            'orden'                 => $this->orden,
            'cuenta'                => $this->whenLoaded('cuenta', fn () => [
                'id'            => $this->cuenta->id,
                'alias'         => $this->cuenta->alias,
                'tipo'          => $this->cuenta->tipo,
                'numero_cuenta' => $this->cuenta->numero_cuenta,
                'banco'         => $this->when(
                    $this->cuenta->relationLoaded('banco'),
                    fn () => $this->cuenta->banco
                        ? ['id' => $this->cuenta->banco->id, 'nombre' => $this->cuenta->banco->nombre]
                        : null
                ),
                'titular'   => $this->when(
                    isset($this->cuenta->titular),
                    fn () => ['id' => $this->cuenta->titular?->id, 'nombre' => $this->cuenta->titular?->nombre]
                ),
                'cliente'   => $this->when(
                    $this->cuenta->relationLoaded('cliente'),
                    fn () => $this->cuenta->cliente
                        ? ['id' => $this->cuenta->cliente->id, 'nombre' => $this->cuenta->cliente->nombre]
                        : null
                ),
            ]),
            'moneda'                => $this->whenLoaded('moneda', fn () => [
                'id'      => $this->moneda->id,
                'codigo'  => $this->moneda->codigo,
                'simbolo' => $this->moneda->simbolo,
            ]),
        ];
    }
}
