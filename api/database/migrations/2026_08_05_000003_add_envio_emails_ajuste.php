<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el ajuste 'envio_emails' (activa/desactiva el envío de correos
 * desde la aplicación). Se inserta si no existe para no pisar un valor
 * que el usuario ya haya cambiado desde el panel de control.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ajustes')->updateOrInsert(
            ['clave' => 'envio_emails'],
            [
                'valor'       => '1',
                'descripcion' => 'Habilita o deshabilita el envío de correos electrónicos desde la aplicación.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('ajustes')->where('clave', 'envio_emails')->delete();
    }
};
