<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de sesiones de usuario (login/logout).
 * Cada login exitoso crea una fila con IP y user agent; el logout la cierra.
 * Las sesiones sin logout se consideran 'expirada' según la vigencia del token Sanctum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('token_id')->nullable()->constrained('personal_access_tokens')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('logout_at')->nullable();
            $table->enum('logout_tipo', ['manual', 'expirada'])->nullable();
            $table->timestamps();

            $table->index(['user_id', 'login_at']);
            $table->index('token_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_usuario');
    }
};
