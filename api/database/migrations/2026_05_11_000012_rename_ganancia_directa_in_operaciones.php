<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->renameColumn('ganancia_directa_usd', 'ganancia_bruta_usd');
            $table->renameColumn('ganancia_directa_ves', 'ganancia_bruta_ves');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->renameColumn('ganancia_bruta_usd', 'ganancia_directa_usd');
            $table->renameColumn('ganancia_bruta_ves', 'ganancia_directa_ves');
        });
    }
};
