<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones_metodo_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_metodo', 80);
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('descripcion', 100);
            $table->enum('tipo_calculo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 20, 8);
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['nombre_metodo', 'activa']);
            $table->index(['cuenta_id', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones_metodo_pago');
    }
};
