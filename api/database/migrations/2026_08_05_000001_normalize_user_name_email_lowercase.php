<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normaliza name y email de los usuarios existentes a minúsculas
 * para mantener consistencia con los mutators del modelo User.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('name')->update([
            'name' => DB::raw('LOWER(TRIM(name))'),
        ]);

        DB::table('users')->whereNotNull('email')->update([
            'email' => DB::raw('LOWER(TRIM(email))'),
        ]);
    }

    public function down(): void
    {
        // No se puede revertir: se pierde la capitalización original.
    }
};
