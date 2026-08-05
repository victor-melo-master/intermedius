<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosPorRolSeeder extends Seeder
{
    /**
     * Crea un usuario de prueba por cada rol del sistema.
     *
     * Idempotente: usa firstOrCreate por email. Si el usuario ya existe
     * (p. ej. admin@test.com creado por AdminUserSeeder), se reutiliza
     * y solo se garantiza el rol. Contraseña común: password123.
     *
     * Ejecutar con: php artisan db:seed --class=UsuariosPorRolSeeder
     */
    public function run(): void
    {
        $usuarios = [
            ['email' => 'admin@test.com',     'name' => 'Super Admin',    'rol' => 'super_admin'],
            ['email' => 'gerente@test.com',   'name' => 'Gerente',        'rol' => 'admin'],
            ['email' => 'operador@test.com',  'name' => 'Operador',       'rol' => 'operador'],
            ['email' => 'pagador@test.com',   'name' => 'Pagador',        'rol' => 'pagador'],
            ['email' => 'contador@test.com',  'name' => 'Contador',       'rol' => 'contador'],
            ['email' => 'lectura@test.com',   'name' => 'Solo Lectura',   'rol' => 'lectura'],
        ];

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password123'),
                    'activo'            => true,
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole($data['rol'])) {
                $user->assignRole($data['rol']);
            }
        }

        $this->command->info('✅ Usuarios de prueba creados (uno por rol). Contraseña: password123');
    }
}
