<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('titular_id')->nullable()->after('id')->constrained('titulares')->nullOnDelete();
            $table->boolean('activo')->default(true)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('activo');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['titular_id']);
            $table->dropColumn(['titular_id', 'activo', 'last_login_at']);
            $table->dropSoftDeletes();
        });
    }
};
