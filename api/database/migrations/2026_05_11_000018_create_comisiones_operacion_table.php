<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones_operacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('tipo', ['cuenta', 'operador', 'metodo_pago', 'manual']);
            $table->nullableMorphs('origen', 'idx_comision_origen');
            $table->string('descripcion', 200);
            $table->decimal('monto', 20, 4);
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->decimal('monto_usd_equivalente', 20, 4);
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('editada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('editada_at')->nullable();
            $table->text('razon_edicion')->nullable();
            $table->timestamps();

            $table->index(['operacion_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones_operacion');
    }
};
