<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            // 1. Primero eliminar la foreign key que depende del índice
            $table->dropForeign(['titular_id']);

            // 2. Ahora sí eliminar el índice único
            $table->dropUnique(['titular_id', 'alias']);

            // 3. Hacer titular_id nullable
            $table->unsignedBigInteger('titular_id')->nullable()->change();

            // 4. Ampliar el ENUM tipo a los 7 valores soportados
            $table->enum('tipo', [
                'banco', 'plataforma', 'cash', 'wallet',
                'zelle', 'efectivo', 'otro',
            ])->change();

            // 5. Recrear la foreign key de titular_id (ahora nullable)
            $table->foreign('titular_id')
                ->references('id')
                ->on('titulares')
                ->restrictOnDelete();

            // 6. Crear los nuevos índices únicos compuestos
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
            // 1. Soltar la foreign key y los índices compuestos nuevos
            $table->dropForeign(['titular_id']);
            $table->dropUnique('cuentas_titular_banco_alias_unique');
            $table->dropUnique('cuentas_cliente_banco_alias_unique');

            // 2. Revertir ENUM a los 4 valores originales
            $table->enum('tipo', ['banco', 'plataforma', 'cash', 'wallet'])->change();

            // 3. Revertir titular_id a NOT NULL
            $table->unsignedBigInteger('titular_id')->nullable(false)->change();

            // 4. Recrear el índice único original
            $table->unique(['titular_id', 'alias']);

            // 5. Recrear la foreign key original
            $table->foreign('titular_id')
                ->references('id')
                ->on('titulares')
                ->restrictOnDelete();
        });
    }
};
