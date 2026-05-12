<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permitir Sobregiro FIFO
    |--------------------------------------------------------------------------
    | Si es false, una operación que intente egresar más cantidad de la que
    | existe en lotes lanzará ValidationException en lugar de crear un
    | consumo con lote_id null.
    */
    'permitir_sobregiro' => env('FIFO_PERMITIR_SOBREGIRO', true),

    /*
    |--------------------------------------------------------------------------
    | Política de Costo en Sobregiro
    |--------------------------------------------------------------------------
    | tasa_movimiento : usa la tasa_a_usd del movimiento que causa el sobregiro
    |                   (ganancia = 0 sobre la cantidad en sobregiro).
    | ultimo_lote     : usa el costo_unitario_usd del último lote histórico del
    |                   titular en esa moneda. Si no hay lotes, usa tasa_movimiento.
    | cero            : asume costo cero. TODO el monto del sobregiro cuenta como
    |                   ganancia. SOLO para auditorías. NO recomendado en producción.
    */
    'politica_costo_sobregiro' => env('FIFO_POLITICA_COSTO_SOBREGIRO', 'tasa_movimiento'),

    /*
    |--------------------------------------------------------------------------
    | Tolerancia de Consumo
    |--------------------------------------------------------------------------
    | Cantidad mínima en unidades para considerar un lote "no agotado".
    | Evita que residuos de redondeo (0.00000001 USDT) generen consumos espurios.
    */
    'tolerancia_consumo' => 0.0001,
];
