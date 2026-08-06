<?php

namespace Tests\Feature;

use App\Models\Titular;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
            ->assertJsonPath('name', 'nuevo usuario')
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
            ->assertJsonPath('name', 'actualizado');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'actualizado']);
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

    // ── avatar ────────────────────────────────────────────────────────

    public function test_update_avatar_convierte_a_webp_512_cuadrado(): void
    {
        Storage::fake('s3');
        $usuario = User::factory()->create();

        $imagen = UploadedFile::fake()->image('foto.png', 100, 200);

        $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->put("/api/v1/usuarios/{$usuario->id}", ['avatar' => $imagen])
            ->assertOk()
            ->assertJsonPath('avatar_path', fn ($v) => is_string($v) && str_ends_with($v, '.webp'));

        $usuario->refresh();
        $this->assertNotNull($usuario->avatar_path);

        $archivo = Storage::disk('s3')->get($usuario->avatar_path);
        $this->assertNotFalse($archivo, 'El avatar no se guardó en el disco.');
        $gd = @imagecreatefromstring($archivo);
        $this->assertNotFalse($gd, 'El archivo guardado no es una imagen decodificable.');
        $this->assertSame(512, imagesx($gd), 'El avatar no tiene 512px de ancho.');
        $this->assertSame(512, imagesy($gd), 'El avatar no tiene 512px de alto.');
        $this->assertNotSame('image/png', (new \finfo(FILEINFO_MIME_TYPE))->buffer($archivo));
    }

    public function test_update_avatar_rechaza_archivo_no_imagen(): void
    {
        $usuario = User::factory()->create();
        $archivo = UploadedFile::fake()->create('falso.txt', 10, 'text/plain');

        $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->put("/api/v1/usuarios/{$usuario->id}", ['avatar' => $archivo])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_avatar_endpoint_requiere_token_y_sirve_webp(): void
    {
        Storage::fake('s3');
        $usuario = User::factory()->create();
        $imagen = UploadedFile::fake()->image('foto.png', 100, 200);
        $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->put("/api/v1/usuarios/{$usuario->id}", ['avatar' => $imagen])
            ->assertOk();

        $this->getJson("/api/v1/usuarios/{$usuario->id}/avatar")->assertUnauthorized();

        $token = $usuario->createToken('test')->plainTextToken;
        $this->get("/api/v1/usuarios/{$usuario->id}/avatar?token={$token}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');
    }

    public function test_avatar_endpoint_404_sin_avatar(): void
    {
        $usuario = User::factory()->create();
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->get("/api/v1/usuarios/{$usuario->id}/avatar?token={$token}")
            ->assertNotFound();
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

    // ── perfil (usuario autenticado) ─────────────────────────────────

    public function test_perfil_devuelve_datos_del_usuario_autenticado(): void
    {
        $usuario = User::factory()->create([
            'telefono' => '+58 412 123 4567',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($usuario)
            ->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonPath('id', $usuario->id)
            ->assertJsonPath('email', $usuario->email)
            ->assertJsonPath('telefono', '+58 412 123 4567')
            ->assertJsonPath('roles', []);
    }

    public function test_perfil_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/perfil')->assertUnauthorized();
    }

    public function test_perfil_update_cambia_telefono_sin_password(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', ['telefono' => '+58 414 555 8899'])
            ->assertOk()
            ->assertJsonPath('telefono', '+58 414 555 8899');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'telefono' => '+58 414 555 8899']);
    }

    public function test_perfil_update_cambia_email_exige_password_actual(): void
    {
        $usuario = User::factory()->create(['email' => 'viejo@example.com']);

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', ['email' => 'nuevo@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password_actual']);
    }

    public function test_perfil_update_cambia_email_con_password_actual_correcta(): void
    {
        Notification::fake();
        $password = 'Str0ng!Pass1';
        $usuario = User::factory()->create([
            'email'             => 'viejo@example.com',
            'password'          => $password,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', [
                'email'           => 'nuevo@example.com',
                'password_actual' => $password,
            ])
            ->assertOk()
            ->assertJsonPath('email', 'nuevo@example.com');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'email' => 'nuevo@example.com']);
        $this->assertNull($usuario->fresh()->email_verified_at, 'El correo nuevo debe quedar sin verificar.');
        Notification::assertSentTo($usuario, VerifyEmailNotification::class);
    }

    public function test_perfil_update_rechaza_password_actual_incorrecta(): void
    {
        $usuario = User::factory()->create(['email' => 'viejo@example.com']);

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', [
                'email'           => 'nuevo@example.com',
                'password_actual' => 'Incorrecta!1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password_actual']);

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'email' => 'viejo@example.com']);
    }

    public function test_perfil_update_cambia_password_con_password_actual(): void
    {
        $password = 'Str0ng!Pass1';
        $usuario = User::factory()->create(['password' => $password]);

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', [
                'password_actual'         => $password,
                'password'                => 'NuevaStr0ng!2',
                'password_confirmation'   => 'NuevaStr0ng!2',
            ])
            ->assertOk();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NuevaStr0ng!2', $usuario->fresh()->password));
    }

    public function test_perfil_update_exige_password_actual_para_cambiar_password(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', [
                'password'              => 'NuevaStr0ng!2',
                'password_confirmation' => 'NuevaStr0ng!2',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password_actual']);
    }

    public function test_perfil_update_valida_email_unico(): void
    {
        User::factory()->create(['email' => 'ocupado@example.com']);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', [
                'email'           => 'ocupado@example.com',
                'password_actual' => 'Str0ng!Pass1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_perfil_update_no_modifica_el_rol(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('operador');

        $this->actingAs($usuario)
            ->patchJson('/api/v1/perfil', [
                'rol' => 'admin',
            ])
            ->assertOk();

        $usuario->refresh();
        $this->assertTrue($usuario->hasRole('operador'));
        $this->assertFalse($usuario->hasRole('admin'));
    }
}
