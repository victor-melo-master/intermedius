<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_operacion')
            ->where('codigo', 'venta_usd')
            ->update(['genera_ganancia' => false]);

        DB::table('operaciones')
            ->where('tipo_operacion_id', fn ($q) => $q->select('id')->from('tipos_operacion')->where('codigo', 'venta_usd'))
            ->update([
                'ganancia_bruta_usd' => 0,
                'ganancia_bruta_ves' => 0,
                'ganancia_neta_usd'  => 0,
                'ganancia_neta_ves'  => 0,
            ]);
    }

    public function down(): void
    {
        DB::table('tipos_operacion')
            ->where('codigo', 'venta_usd')
            ->update(['genera_ganancia' => true]);
    }
};
