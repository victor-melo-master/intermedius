<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Cuenta;
use App\Models\Titular;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[IgnoreDeprecations]
class TitularEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;
    private User $lectura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');

        $this->lectura = User::factory()->create(['activo' => true]);
        $this->lectura->assignRole('lectura');
    }

    public function test_index_lista_titulares_activos(): void
    {
        Titular::factory()->count(2)->create(['activo' => true]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/titulares')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/titulares')->assertUnauthorized();
    }

    public function test_index_excluye_inactivos_por_defecto(): void
    {
        Titular::factory()->create(['activo' => true]);
        Titular::factory()->create(['activo' => false]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/titulares')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_store_crea_titular(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/titulares', ['nombre' => 'Nuevo Titular']);

        $response->assertCreated()
            ->assertJsonPath('nombre', 'Nuevo Titular');
    }

    public function test_store_requiere_nombre(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/titulares', [])
            ->assertStatus(422);
    }

    public function test_store_admin_solo(): void
    {
        $this->actingAs($this->operador)
            ->postJson('/api/v1/titulares', ['nombre' => 'Test'])
            ->assertForbidden();
    }

    public function test_show_muestra_titular(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/titulares/{$titular->id}")
            ->assertOk()
            ->assertJsonPath('id', $titular->id);
    }

    public function test_show_404_si_no_existe(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/titulares/99999')
            ->assertNotFound();
    }

    public function test_update_admin_puede(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/titulares/{$titular->id}", ['nombre' => 'Modificado'])
            ->assertOk();
    }

    public function test_update_operador_no_puede(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->operador)
            ->patchJson("/api/v1/titulares/{$titular->id}", ['nombre' => 'Hack'])
            ->assertForbidden();
    }

    public function test_update_lectura_no_puede(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->lectura)
            ->patchJson("/api/v1/titulares/{$titular->id}", ['nombre' => 'Hack'])
            ->assertForbidden();
    }

    public function test_destroy_admin_elimina(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/titulares/{$titular->id}")
            ->assertNoContent();
    }

    public function test_destroy_operador_no_puede(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->operador)
            ->deleteJson("/api/v1/titulares/{$titular->id}")
            ->assertForbidden();
    }

    public function test_destroy_lectura_no_puede(): void
    {
        $titular = Titular::factory()->create();

        $this->actingAs($this->lectura)
            ->deleteJson("/api/v1/titulares/{$titular->id}")
            ->assertForbidden();
    }
}
