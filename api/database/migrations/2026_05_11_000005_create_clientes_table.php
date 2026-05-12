<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('alias')->nullable();
            $table->string('documento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('notas')->nullable();
            $table->decimal('saldo_cache_usd', 20, 4)->default(0);
            $table->timestamp('saldo_cache_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->fullText(['nombre', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
