<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modificar el enum existente de 'estado' para incluir los nuevos valores
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE operaciones MODIFY COLUMN estado varchar(50) NOT NULL DEFAULT 'en_espera'");
        }

        // Agregar las nuevas columnas
        Schema::table('operaciones', function (Blueprint $table) {
            $table->json('tasas_snapshot')->nullable()->after('fuente_tasa_mercado')->comment('Snapshot de tasas BCV/USDT al momento de la solicitud');
            $table->timestamp('en_progreso_at')->nullable()->after('cancelada_at');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropColumn(['tasas_snapshot', 'en_progreso_at']);
        });

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE operaciones MODIFY COLUMN estado enum('en_espera','en_proceso','concluida','cancelada') NOT NULL DEFAULT 'en_espera'");
        }
    }
};
