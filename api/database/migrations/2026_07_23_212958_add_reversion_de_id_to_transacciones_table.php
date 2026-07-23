<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->foreignId('reversion_de_id')
                ->nullable()
                ->after('confirmada_por_id')
                ->constrained('transacciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropForeign(['reversion_de_id']);
            $table->dropColumn('reversion_de_id');
        });
    }
};
