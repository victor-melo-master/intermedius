<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->decimal('tasa_sugerida', 20, 8)->nullable()->after('tasa_aplicada');
            $table->foreignId('tasa_diaria_id')->nullable()->after('tasa_sugerida')
                ->constrained('tasas_diarias')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('sin_tasa_referencia')->default(false)->after('tasa_diaria_id');
            $table->decimal('total_comisiones_usd', 20, 4)->default(0)->after('ganancia_real_ves');
            $table->decimal('total_comisiones_ves', 20, 2)->default(0)->after('total_comisiones_usd');
            $table->decimal('ganancia_neta_usd', 20, 4)->default(0)->after('total_comisiones_ves');
            $table->decimal('ganancia_neta_ves', 20, 2)->default(0)->after('ganancia_neta_usd');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropForeign(['tasa_diaria_id']);
            $table->dropColumn([
                'tasa_sugerida',
                'tasa_diaria_id',
                'sin_tasa_referencia',
                'total_comisiones_usd',
                'total_comisiones_ves',
                'ganancia_neta_usd',
                'ganancia_neta_ves',
            ]);
        });
    }
};
