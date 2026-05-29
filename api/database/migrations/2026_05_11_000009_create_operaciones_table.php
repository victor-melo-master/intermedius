<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('tipo_operacion_id')->constrained('tipos_operacion')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('categoria_gasto_id')->nullable()->constrained('categorias_gasto')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('operador_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->decimal('tasa_aplicada', 20, 8)->nullable();
            $table->decimal('tasa_mercado_snapshot', 20, 8)->nullable();
            $table->string('fuente_tasa_mercado', 30)->nullable();

            $table->decimal('ganancia_directa_usd', 20, 4)->default(0);
            $table->decimal('ganancia_real_usd', 20, 4)->nullable();
            $table->decimal('ganancia_directa_ves', 20, 2)->default(0);
            $table->decimal('ganancia_real_ves', 20, 2)->nullable();

            $table->string('referencia', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('estatus', ['verificado', 'en_revision', 'sin_verificar'])->default('sin_verificar');
            $table->timestamp('verificado_at')->nullable();
            $table->foreignId('verificado_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('origen', ['manual', 'importado', 'ajuste_apertura'])->default('manual');
            $table->string('origen_referencia', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Idempotencia del importador (Fase 7): MySQL permite múltiples NULL en UNIQUE.
            $table->unique('origen_referencia');

            $table->index(['fecha', 'tipo_operacion_id']);
            $table->index('estatus');
            $table->index('cliente_id');
            $table->index('operador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones');
    }
};
