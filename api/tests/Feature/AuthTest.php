<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $password = 'password';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);
        Cache::flush();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt($this->password),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('operador');
    }

    public function test_login_exitoso_devuelve_token_y_usuario()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_login_fallido_con_credenciales_incorrectas()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciales incorrectas.']);
    }

    public function test_login_por_username_devuelve_token()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $this->user->name,
            'password' => $this->password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_login_por_email_con_campo_login_devuelve_token()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'test@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_login_fallido_sin_email()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => $this->password,
        ]);

        $response->assertStatus(422);
    }

    public function test_login_bloqueado_por_demasiados_intentos()
    {
        LoginAttempt::factory()->count(5)->create([
            'email' => 'test@example.com',
            'successful' => false,
            'attempted_at' => now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(429);
    }

    public function test_login_con_email_no_verificado()
    {
        $userNoVerificado = User::factory()->unverified()->create([
            'email' => 'noverificado@example.com',
            'password' => bcrypt($this->password),
            'activo' => true,
        ]);
        $userNoVerificado->assignRole('operador');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'noverificado@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Debe verificar su correo electrónico antes de iniciar sesión.']);
    }

    public function test_login_con_usuario_inactivo()
    {
        $userInactivo = User::factory()->create([
            'email' => 'inactivo@example.com',
            'password' => bcrypt($this->password),
            'activo' => false,
            'email_verified_at' => now(),
        ]);
        $userInactivo->assignRole('operador');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactivo@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Usuario inactivo.']);
    }

    public function test_me_devuelve_usuario_autenticado()
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]);
    }

    public function test_me_sin_token_devuelve_401()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_logout_revoca_token()
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sesión cerrada correctamente.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_sin_token_devuelve_401()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_ruta_protegida_sin_token_devuelve_401()
    {
        $response = $this->getJson('/api/v1/operaciones');

        $response->assertStatus(401);
    }
}
