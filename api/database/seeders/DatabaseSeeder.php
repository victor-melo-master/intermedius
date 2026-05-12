<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogosBaseSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@casacambio.dev'],
            [
                'name'     => 'Administrador',
                'password' => bcrypt('password'),
                'activo'   => true,
            ]
        );
        $admin->assignRole('super_admin');
    }
}
