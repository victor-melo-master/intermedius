<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE clientes MODIFY saldo_cache_usd DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE cuentas MODIFY saldo_cache DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY monto_comision DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY ganancia_bruta_usd DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY ganancia_real_usd DECIMAL(20,2) DEFAULT NULL');
        DB::statement('ALTER TABLE operaciones MODIFY total_comisiones_usd DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY ganancia_neta_usd DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE movimientos MODIFY monto DECIMAL(20,2) NOT NULL');
        DB::statement('ALTER TABLE movimientos MODIFY monto_usd_equivalente DECIMAL(20,2) NOT NULL');
        DB::statement('ALTER TABLE transacciones MODIFY monto DECIMAL(20,2) NOT NULL');
        DB::statement('ALTER TABLE comisiones_operacion MODIFY monto DECIMAL(20,2) NOT NULL');
        DB::statement('ALTER TABLE comisiones_operacion MODIFY monto_usd_equivalente DECIMAL(20,2) NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE clientes MODIFY saldo_cache_usd DECIMAL(20,4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE cuentas MODIFY saldo_cache DECIMAL(20,4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY monto_comision DECIMAL(20,4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY ganancia_bruta_usd DECIMAL(20,4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY ganancia_real_usd DECIMAL(20,4) DEFAULT NULL');
        DB::statement('ALTER TABLE operaciones MODIFY total_comisiones_usd DECIMAL(20,4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE operaciones MODIFY ganancia_neta_usd DECIMAL(20,4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE movimientos MODIFY monto DECIMAL(20,4) NOT NULL');
        DB::statement('ALTER TABLE movimientos MODIFY monto_usd_equivalente DECIMAL(20,4) NOT NULL');
        DB::statement('ALTER TABLE transacciones MODIFY monto DECIMAL(20,4) NOT NULL');
        DB::statement('ALTER TABLE comisiones_operacion MODIFY monto DECIMAL(20,4) NOT NULL');
        DB::statement('ALTER TABLE comisiones_operacion MODIFY monto_usd_equivalente DECIMAL(20,4) NOT NULL');
    }
};
