<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Añade 'completada' al enum de estado_pool.
     *
     * Las operaciones cerradas (cierre del flujo multi-paso) y las ventas
     * directas guardan estado_pool = 'completada'; las BDs creadas desde
     * migraciones usan string y ya lo permitían, pero las creadas con
     * docker/mysql/00-init.sh definen enum sin ese valor (Warning 1265).
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE operaciones MODIFY COLUMN estado_pool enum('pendiente','asignada','pagada','cancelada','completada') NOT NULL DEFAULT 'pendiente'");
        }
    }

    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE operaciones MODIFY COLUMN estado_pool enum('pendiente','asignada','pagada','cancelada') NOT NULL DEFAULT 'pendiente'");
        }
    }
};
