<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->string('estado', 50)->default('pendiente')->after('orden');
            $table->text('motivo_rechazo')->nullable()->after('estado');
            $table->timestamp('validada_en')->nullable()->after('motivo_rechazo');
            $table->foreignId('validada_por_id')->nullable()->after('validada_en')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'motivo_rechazo', 'validada_en', 'validada_por_id']);
        });
    }
};
