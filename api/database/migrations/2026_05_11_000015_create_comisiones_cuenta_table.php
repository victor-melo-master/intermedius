<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('banco_id')->nullable()->constrained('bancos')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('descripcion', 100);
            $table->enum('tipo_calculo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 20, 8);
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->enum('aplica_a', ['ingreso', 'egreso', 'ambos']);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['cuenta_id', 'activa']);
            $table->index(['banco_id', 'activa']);
            $table->index(['vigente_desde', 'vigente_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones_cuenta');
    }
};
