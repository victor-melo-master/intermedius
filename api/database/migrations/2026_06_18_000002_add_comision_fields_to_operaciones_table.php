<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->boolean('genera_comision')->default(false)->after('tasa_aplicada');
            $table->decimal('monto_comision', 20, 4)->default(0)->after('genera_comision');
            // tipo_comision: 'pago_movil', 'otros_bancos', 'mismo_banco', 'manual'
            $table->string('tipo_comision', 50)->nullable()->after('monto_comision');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropColumn([
                'genera_comision',
                'monto_comision',
                'tipo_comision',
            ]);
        });
    }
};
