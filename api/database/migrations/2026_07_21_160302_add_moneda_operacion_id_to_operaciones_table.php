<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('moneda_operacion_id')->nullable()->after('tipo_operacion_id');
            $table->foreign('moneda_operacion_id')->references('id')->on('monedas')->onDelete('SET NULL');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropForeign(['moneda_operacion_id']);
            $table->dropColumn('moneda_operacion_id');
        });
    }
};
