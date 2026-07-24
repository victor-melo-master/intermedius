<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flujo_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->decimal('monto', 20, 2);
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->string('descripcion')->nullable();
            $table->foreignId('operacion_id')->nullable()->constrained('operaciones')->nullOnDelete();
            $table->foreignId('transaccion_id')->nullable()->constrained('transacciones')->nullOnDelete();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cuenta_id', 'created_at']);
            $table->index(['cuenta_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flujo_cuentas');
    }
};
