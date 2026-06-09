<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            // 1. titular_id pasa a ser nullable (cuentas de cliente no tienen titular)
            $table->foreignId('titular_id')
                ->nullable()
                ->change();

            // 2. Ampliar el ENUM tipo a los 7 valores soportados
            $table->enum('tipo', [
                'banco', 'plataforma', 'cash', 'wallet',
                'zelle', 'efectivo', 'otro',
            ])->change();
        });

        Schema::table('cuentas', function (Blueprint $table) {
            // 3. Reemplazar el índice único ['titular_id', 'alias'] por dos índices
            //    flexibles que cubren tanto cuentas de titular como de cliente.
            $table->dropUnique(['titular_id', 'alias']);

            $table->unique(
                ['titular_id', 'banco_id', 'alias'],
                'cuentas_titular_banco_alias_unique'
            );
            $table->unique(
                ['cliente_id', 'banco_id', 'alias'],
                'cuentas_cliente_banco_alias_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            // Revertir índices
            $table->dropUnique('cuentas_titular_banco_alias_unique');
            $table->dropUnique('cuentas_cliente_banco_alias_unique');

            $table->unique(['titular_id', 'alias']);
        });

        Schema::table('cuentas', function (Blueprint $table) {
            // Revertir ENUM a los 4 valores originales
            $table->enum('tipo', ['banco', 'plataforma', 'cash', 'wallet'])->change();

            // Revertir titular_id a NOT NULL
            $table->foreignId('titular_id')
                ->nullable(false)
                ->change();
        });
    }
};
