<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_mercado', function (Blueprint $table) {
            $table->id();
            $table->string('fuente', 30);
            $table->foreignId('moneda_base_id')->constrained('monedas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('moneda_cotizada_id')->constrained('monedas')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('valor', 20, 8);
            $table->timestamp('capturado_en')->index();
            $table->json('payload_original')->nullable();
            $table->timestamps();

            $table->index(['fuente', 'capturado_en'], 'idx_tasas_fuente_capturado');
            $table->index(['moneda_base_id', 'moneda_cotizada_id', 'capturado_en'], 'idx_tasas_par_capturado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_mercado');
    }
};
