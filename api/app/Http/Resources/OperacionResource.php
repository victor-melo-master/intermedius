<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for transforming Operacion models into JSON responses.
 * Includes nested relationships (movements, operator, clients, etc.)
 * and formats monetary values as strings to avoid floating-point issues.
 */
class OperacionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'fecha'                 => $this->fecha,
            'estatus'               => $this->estatus,
            'origen'                => $this->origen,
            'origen_referencia'     => $this->origen_referencia,
            'referencia'            => $this->referencia,
            'descripcion'           => $this->descripcion,

            'monto_solicitado'      => $this->monto_solicitado !== null ? (string) $this->monto_solicitado : null,
            'estado'                => $this->estado,

            'tasa_aplicada'         => (string) $this->tasa_aplicada,
            'tasa_compra'          => (string) $this->tasa_compra,
            'tasa_venta'           => (string) $this->tasa_venta,
            'tasa_mercado_snapshot' => (string) $this->tasa_mercado_snapshot,
            'fuente_tasa_mercado'   => $this->fuente_tasa_mercado,

            'tasa_sugerida'          => (string) $this->tasa_sugerida,
            'tasa_diaria_id'         => $this->tasa_diaria_id,
            'sin_tasa_referencia'    => (bool) $this->sin_tasa_referencia,

            'ganancia'              => [
                'bruta_usd'   => (string) $this->ganancia_bruta_usd,
                'bruta_ves'   => (string) $this->ganancia_bruta_ves,
                'real_usd'    => $this->ganancia_real_usd !== null ? (string) $this->ganancia_real_usd : null,
                'real_ves'    => $this->ganancia_real_ves !== null ? (string) $this->ganancia_real_ves : null,
                'neta_usd'    => (string) $this->ganancia_neta_usd,
                'neta_ves'    => (string) $this->ganancia_neta_ves,
            ],

            'comisiones_total'       => [
                'usd' => (string) $this->total_comisiones_usd,
                'ves' => (string) $this->total_comisiones_ves,
            ],

            'genera_comision'        => (bool) $this->genera_comision,
            'monto_comision'         => (string) $this->monto_comision,
            'tipo_comision'          => $this->tipo_comision,

            'verificado_at'         => $this->verificado_at?->toIso8601String(),

            'estado_pool'           => $this->estado_pool,
            'pagador_id'            => $this->pagador_id,
            'asignada_at'           => $this->asignada_at?->toIso8601String(),
            'pagada_at'             => $this->pagada_at?->toIso8601String(),
            'cancelada_at'          => $this->cancelada_at?->toIso8601String(),
            'motivo_cancelacion'    => $this->motivo_cancelacion,
            'pagador'               => $this->whenLoaded('pagador', fn () => $this->pagador ? [
                'id'   => $this->pagador->id,
                'name' => $this->pagador->name,
            ] : null),

            'tipo_operacion'        => $this->whenLoaded('tipoOperacion', fn () => [
                'id'     => $this->tipoOperacion->id,
                'codigo' => $this->tipoOperacion->codigo,
                'nombre' => $this->tipoOperacion->nombre,
            ]),
            'cliente'               => $this->whenLoaded('cliente', fn () => $this->cliente ? [
                'id'     => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'alias'  => $this->cliente->alias,
            ] : null),
            'cliente_emisor'       => $this->whenLoaded('clienteEmisor', fn () => $this->clienteEmisor ? [
                'id'     => $this->clienteEmisor->id,
                'nombre' => $this->clienteEmisor->nombre,
                'alias'  => $this->clienteEmisor->alias,
            ] : null),
            'cliente_receptor'     => $this->whenLoaded('clienteReceptor', fn () => $this->clienteReceptor ? [
                'id'     => $this->clienteReceptor->id,
                'nombre' => $this->clienteReceptor->nombre,
                'alias'  => $this->clienteReceptor->alias,
            ] : null),
            'categoria_gasto'       => $this->whenLoaded('categoriaGasto', fn () => $this->categoriaGasto ? [
                'id'     => $this->categoriaGasto->id,
                'nombre' => $this->categoriaGasto->nombre,
            ] : null),
            'operador'              => $this->whenLoaded('operador', fn () => [
                'id'   => $this->operador->id,
                'name' => $this->operador->name,
            ]),
            'verificado_por'        => $this->whenLoaded('verificadoPor', fn () => $this->verificadoPor ? [
                'id'   => $this->verificadoPor->id,
                'name' => $this->verificadoPor->name,
            ] : null),
            'movimientos'           => MovimientoResource::collection($this->whenLoaded('movimientos')),
            'transacciones'         => TransaccionResource::collection($this->whenLoaded('transacciones')),

            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
