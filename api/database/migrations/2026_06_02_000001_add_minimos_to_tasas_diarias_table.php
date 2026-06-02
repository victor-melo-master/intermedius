<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasas_diarias', function (Blueprint $table) {
            $table->decimal('tasa_compra_minima', 20, 8)->nullable()->after('tasa_compra');
            $table->decimal('tasa_venta_minima', 20, 8)->nullable()->after('tasa_venta');
        });
    }

    public function down(): void
    {
        Schema::table('tasas_diarias', function (Blueprint $table) {
            $table->dropColumn(['tasa_compra_minima', 'tasa_venta_minima']);
        });
    }
};
