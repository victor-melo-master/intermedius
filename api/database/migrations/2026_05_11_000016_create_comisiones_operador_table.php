<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones_operador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titular_id')->constrained('titulares')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('tipo_operacion_id')->nullable()->constrained('tipos_operacion')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('descripcion', 100);
            $table->enum('tipo_calculo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 20, 8);
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->enum('base_calculo', ['monto_operacion', 'ganancia_bruta'])->default('monto_operacion');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['titular_id', 'activa']);
            $table->index(['vigente_desde', 'vigente_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones_operador');
    }
};
