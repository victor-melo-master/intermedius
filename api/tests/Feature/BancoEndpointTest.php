<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Banco;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[IgnoreDeprecations]
class BancoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    public function test_index_lista_bancos_ordenados(): void
    {
        Banco::factory()->create(['nombre' => 'BBVA']);
        Banco::factory()->create(['nombre' => 'Banco de Venezuela']);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/bancos');

        $response->assertOk()
            ->assertJsonCount(15); // 13 seed + 2 factory
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/bancos')->assertUnauthorized();
    }

    public function test_store_crea_banco(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/bancos', ['nombre' => 'Banco Test']);

        $response->assertCreated()
            ->assertJsonPath('nombre', 'Banco Test');
    }

    public function test_store_requiere_nombre(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/bancos', [])
            ->assertStatus(422);
    }

    public function test_store_admin_solo(): void
    {
        $this->actingAs($this->operador)
            ->postJson('/api/v1/bancos', ['nombre' => 'Test'])
            ->assertForbidden();
    }

    public function test_store_con_codigo_y_pais(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/bancos', [
            'nombre' => 'Banco Nacional',
            'codigo' => '0175',
            'pais'   => 'VE',
        ]);

        $response->assertCreated()
            ->assertJsonPath('codigo', '0175')
            ->assertJsonPath('pais', 'VE');
    }

    public function test_show_muestra_banco(): void
    {
        $banco = Banco::factory()->create();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/bancos/{$banco->id}")
            ->assertOk()
            ->assertJsonPath('id', $banco->id);
    }

    public function test_show_404_si_no_existe(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/bancos/99999')->assertNotFound();
    }

    public function test_update_modifica_banco(): void
    {
        $banco = Banco::factory()->create(['nombre' => 'Original']);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/bancos/{$banco->id}", ['nombre' => 'Modificado']);

        $response->assertOk()->assertJsonPath('nombre', 'Modificado');
    }

    public function test_update_operador_no_puede(): void
    {
        $banco = Banco::factory()->create();

        $this->actingAs($this->operador)
            ->patchJson("/api/v1/bancos/{$banco->id}", ['nombre' => 'Hack'])
            ->assertForbidden();
    }

    public function test_destroy_elimina_banco(): void
    {
        $banco = Banco::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/bancos/{$banco->id}")
            ->assertNoContent();

        $this->assertModelMissing($banco);
    }

    public function test_destroy_operador_no_puede(): void
    {
        $banco = Banco::factory()->create();

        $this->actingAs($this->operador)
            ->deleteJson("/api/v1/bancos/{$banco->id}")
            ->assertForbidden();
    }
}
