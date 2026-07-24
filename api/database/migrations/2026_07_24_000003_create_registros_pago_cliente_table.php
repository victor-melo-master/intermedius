<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_pago_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('metodo_pago', 50);
            $table->string('alias', 255);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['cliente_id', 'metodo_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_pago_cliente');
    }
};
