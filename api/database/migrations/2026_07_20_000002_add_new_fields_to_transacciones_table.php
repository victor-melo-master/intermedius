<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->decimal('tasa_aplicada', 20, 8)->nullable()->after('monto')->comment('Tasa de cambio aplicada en esta transaccion');
            $table->json('tasas_snapshot')->nullable()->after('tasa_aplicada')->comment('Snapshot de tasas BCV/USDT al momento de la transaccion');
            $table->string('metodo_pago', 50)->nullable()->after('tasas_snapshot')->comment('pago_movil, zelle, binance, efectivo, transferencia, otro');
        });

        // Agregar 'confirmada' y 'revertida' al enum de estado
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE transacciones MODIFY COLUMN estado varchar(50) NOT NULL DEFAULT 'pendiente'");
        }

        // Renombrar validada_en -> confirmada_en y validada_por_id -> confirmada_por_id
        Schema::table('transacciones', function (Blueprint $table) {
            $table->renameColumn('validada_en', 'confirmada_en');
            $table->renameColumn('validada_por_id', 'confirmada_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropColumn(['tasa_aplicada', 'tasas_snapshot', 'metodo_pago']);
            $table->renameColumn('confirmada_en', 'validada_en');
            $table->renameColumn('confirmada_por_id', 'validada_por_id');
        });

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE transacciones MODIFY COLUMN estado enum('pendiente','validada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente'");
        }
    }
};
