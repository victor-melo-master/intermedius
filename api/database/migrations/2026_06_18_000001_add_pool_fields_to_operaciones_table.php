<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->enum('estado_pool', [
                'pendiente', 'asignada', 'pagada', 'cancelada',
            ])->default('pendiente')->after('estatus');

            $table->foreignId('pagador_id')->nullable()->after('estado_pool')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('asignada_at')->nullable()->after('pagador_id');
            $table->timestamp('pagada_at')->nullable()->after('asignada_at');
            $table->timestamp('cancelada_at')->nullable()->after('pagada_at');
            $table->text('motivo_cancelacion')->nullable()->after('cancelada_at');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropForeign(['pagador_id']);
            $table->dropColumn([
                'estado_pool',
                'pagador_id',
                'asignada_at',
                'pagada_at',
                'cancelada_at',
                'motivo_cancelacion',
            ]);
        });
    }
};
