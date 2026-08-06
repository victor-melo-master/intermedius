<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la ruta del avatar de perfil (webp) a la tabla de clientes.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('avatar_path', 255)->nullable()->after('email');
        });
    }

    /**
     * Reversión: elimina la columna avatar_path.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
