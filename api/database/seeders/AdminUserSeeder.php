<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('No se puede ejecutar AdminUserSeeder en producción. Cree el admin manualmente.');
            return;
        }

        $password = Str::random(16);

        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin Principal',
                'password' => bcrypt($password),
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $this->command->info("✓ Usuario admin creado con rol super_admin");
        $this->command->warn("Contraseña generada: {$password}"); // Solo visible en consola
    }
}
