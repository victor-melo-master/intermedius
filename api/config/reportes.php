<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reporte Mensual de Comisiones de Operadores
    |--------------------------------------------------------------------------
    */
    'comisiones_operadores' => [
        'enviar_email'   => env('REPORTE_COMISIONES_EMAIL', false),

        /*
        | CSV de emails de destinatarios (ej: "admin@empresa.com,contador@empresa.com").
        | Solo se usan si enviar_email = true y la lista no está vacía.
        */
        'destinatarios'  => env('REPORTE_COMISIONES_DESTINATARIOS', ''),

        /*
        | Directorio base en storage/app/ donde se guardan los reportes.
        */
        'storage_path'   => 'reportes/comisiones',
    ],
];
