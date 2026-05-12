<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titular_id')->constrained('titulares')->restrictOnDelete();
            $table->foreignId('banco_id')->nullable()->constrained('bancos')->nullOnDelete();
            $table->foreignId('moneda_id')->constrained('monedas')->restrictOnDelete();
            $table->string('alias');
            $table->enum('tipo', ['banco', 'plataforma', 'cash', 'wallet']);
            $table->string('numero_cuenta')->nullable();
            $table->decimal('saldo_cache', 20, 4)->default(0);
            $table->timestamp('saldo_cache_at')->nullable();
            $table->boolean('activa')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['titular_id', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
