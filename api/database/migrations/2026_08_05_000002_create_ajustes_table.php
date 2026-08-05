<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de ajustes generales de la aplicación (clave → valor).
 * Contiene por defecto la opción 'password_segura'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        DB::table('ajustes')->insert([
            'clave'       => 'password_segura',
            'valor'       => '1',
            'descripcion' => 'Rechaza contraseñas comprometidas en filtraciones públicas (HIBP).',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
