<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pares Principales de Monedas
    |--------------------------------------------------------------------------
    | Pares que el sistema debe monitorear para alertas de tasa faltante.
    | Formato: 'BASE/COTIZADA'. Se usan en AlertarTasasFaltantesJob y
    | en el endpoint de dashboard (/api/v1/dashboard/general).
    */
    'pares_principales' => ['USD/VES', 'USDT/VES'],

    /*
    |--------------------------------------------------------------------------
    | Moneda Contable de Referencia
    |--------------------------------------------------------------------------
    | Moneda usada como denominador común para equivalencias y ganancias.
    */
    'moneda_referencia' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Moneda Fiat Local
    |--------------------------------------------------------------------------
    | Moneda local excluida del costeo FIFO (no se crea lote para ella).
    */
    'moneda_local' => 'VES',
];
