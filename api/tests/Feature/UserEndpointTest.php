<?php

namespace Tests\Feature;

use App\Models\Titular;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['activo' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    // ── index ────────────────────────────────────────────────────────

    public function test_index_lista_usuarios_activos(): void
    {
        User::factory()->count(3)->create(['activo' => true]);
        // setUp creates 3 users (admin, superAdmin, operador) = 6 total

        $this->actingAs($this->admin)
            ->getJson('/api/v1/usuarios')
            ->assertOk()
            ->assertJsonCount(6);
    }

    public function test_index_excluye_soft_deleted(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->delete(); // sets deleted_at

        $this->actingAs($this->admin)
            ->getJson('/api/v1/usuarios')
            ->assertOk()
            ->assertJsonCount(3); // 3 setUp only
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/usuarios')->assertUnauthorized();
    }

    public function test_index_solo_admin_super_admin(): void
    {
        $this->actingAs($this->operador)
            ->getJson('/api/v1/usuarios')
            ->assertForbidden();
    }

    public function test_index_incluye_titular(): void
    {
        $titular = Titular::factory()->create();
        User::factory()->create(['titular_id' => $titular->id, 'activo' => true]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/usuarios')
            ->assertOk()
            ->assertJsonFragment(['titular_id' => $titular->id]);
    }

    // ── store ────────────────────────────────────────────────────────

    public function test_store_crea_usuario(): void
    {
        Notification::fake();

        $payload = [
            'name'     => 'Nuevo Usuario',
            'email'    => 'nuevo@example.com',
            'password' => 'Str0ng!Pass1',
            'rol'      => 'operador',
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/usuarios', $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Nuevo Usuario')
            ->assertJsonPath('email', 'nuevo@example.com');

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);

        Notification::assertSentTo(
            User::where('email', 'nuevo@example.com')->first(),
            VerifyEmailNotification::class,
        );
    }

    public function test_store_valida_campos_requeridos(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/usuarios', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'rol']);
    }

    public function test_store_valida_email_unico(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/usuarios', [
                'name'     => 'Test',
                'email'    => 'existente@example.com',
                'password' => 'Str0ng!Pass1',
                'rol'      => 'operador',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_valida_rol_invalido(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/usuarios', [
                'name'     => 'Test',
                'email'    => 'test@example.com',
                'password' => 'Str0ng!Pass1',
                'rol'      => 'rol_inexistente',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rol']);
    }

    public function test_store_super_admin_puede_crear(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/usuarios', [
                'name'     => 'Por SuperAdmin',
                'email'    => 'super-creado@example.com',
                'password' => 'Str0ng!Pass1',
                'rol'      => 'contador',
            ])
            ->assertCreated();
    }

    public function test_store_solo_admin_super_admin(): void
    {
        $this->actingAs($this->operador)
            ->postJson('/api/v1/usuarios', [
                'name'     => 'Test',
                'email'    => 'test@example.com',
                'password' => 'Str0ng!Pass1',
                'rol'      => 'operador',
            ])
            ->assertForbidden();
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/usuarios', [
            'name'     => 'Test',
            'email'    => 'test@example.com',
            'password' => 'Str0ng!Pass1',
            'rol'      => 'operador',
        ])->assertUnauthorized();
    }

    // ── update ───────────────────────────────────────────────────────

    public function test_update_actualiza_nombre(): void
    {
        $usuario = User::factory()->create(['name' => 'Original']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/usuarios/{$usuario->id}", ['name' => 'Actualizado'])
            ->assertOk()
            ->assertJsonPath('name', 'Actualizado');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Actualizado']);
    }

    public function test_update_cambia_rol(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('operador');

        $this->actingAs($this->admin)
            ->putJson("/api/v1/usuarios/{$usuario->id}", ['rol' => 'contador'])
            ->assertOk();

        $usuario->refresh();
        $this->assertTrue($usuario->hasRole('contador'));
        $this->assertFalse($usuario->hasRole('operador'));
    }

    public function test_update_password_elimina_tokens(): void
    {
        $usuario = User::factory()->create();
        $usuario->createToken('test');

        $this->actingAs($this->admin)
            ->putJson("/api/v1/usuarios/{$usuario->id}", ['password' => 'NuevaStr0ng!1']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_update_no_requiere_password(): void
    {
        $usuario = User::factory()->create(['name' => 'Original']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/usuarios/{$usuario->id}", ['name' => 'Solo nombre'])
            ->assertOk();
    }

    public function test_update_solo_admin_super_admin(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($this->operador)
            ->putJson("/api/v1/usuarios/{$usuario->id}", ['name' => 'No debería'])
            ->assertForbidden();
    }

    public function test_update_requires_authentication(): void
    {
        $usuario = User::factory()->create();

        $this->putJson("/api/v1/usuarios/{$usuario->id}", ['name' => 'X'])
            ->assertUnauthorized();
    }

    // ── destroy ──────────────────────────────────────────────────────

    public function test_destroy_desactiva_usuario(): void
    {
        $usuario = User::factory()->create(['activo' => true]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/usuarios/{$usuario->id}")
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'activo' => false]);
    }

    public function test_destroy_solo_admin_super_admin(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($this->operador)
            ->deleteJson("/api/v1/usuarios/{$usuario->id}")
            ->assertForbidden();
    }

    public function test_destroy_requires_authentication(): void
    {
        $usuario = User::factory()->create();

        $this->deleteJson("/api/v1/usuarios/{$usuario->id}")
            ->assertUnauthorized();
    }
}
