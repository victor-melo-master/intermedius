<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('moneda_id')->constrained('monedas')->cascadeOnUpdate()->restrictOnDelete();

            $table->decimal('monto', 20, 4);
            $table->decimal('tasa_a_usd', 20, 8);
            $table->decimal('monto_usd_equivalente', 20, 4);

            $table->unsignedSmallInteger('orden')->default(1);
            $table->timestamps();

            $table->index(['cuenta_id', 'created_at']);
            $table->index(['operacion_id', 'orden']);
            $table->index(['cuenta_id', 'moneda_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
