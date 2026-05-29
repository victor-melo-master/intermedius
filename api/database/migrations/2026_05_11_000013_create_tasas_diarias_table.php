<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_diarias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('moneda_base_id')->constrained('monedas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('moneda_cotizada_id')->constrained('monedas')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('tasa_compra', 20, 8);
            $table->decimal('tasa_venta', 20, 8);
            $table->foreignId('definida_por_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('notas')->nullable();
            $table->timestamp('vigente_desde');
            $table->timestamp('vigente_hasta')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'moneda_base_id', 'moneda_cotizada_id'], 'idx_tasa_dia_par');
            $table->index(['moneda_base_id', 'moneda_cotizada_id', 'vigente_desde', 'vigente_hasta'], 'idx_tasa_vigencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_diarias');
    }
};
