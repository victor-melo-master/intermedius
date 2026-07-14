<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Moneda;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[IgnoreDeprecations]
class MonedaEndpointTest extends TestCase
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

    public function test_index_lista_monedas_ordenadas(): void
    {
        Moneda::factory()->create(['codigo' => 'GBP']);
        Moneda::factory()->create(['codigo' => 'JPY']);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/monedas');

        $response->assertOk()
            ->assertJsonCount(7) // 5 seed + 2 factory
            ->assertJsonPath('0.codigo', 'COP')
            ->assertJsonPath('1.codigo', 'EUR')
            ->assertJsonPath('2.codigo', 'GBP')
            ->assertJsonPath('3.codigo', 'JPY');
            // USDT, USD, VES follow
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/monedas')->assertUnauthorized();
    }

    public function test_store_crea_moneda(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/monedas', [
            'codigo'    => 'GBP',
            'nombre'    => 'Libra Esterlina',
            'decimales' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('codigo', 'GBP')
            ->assertJsonPath('nombre', 'Libra Esterlina');
    }

    public function test_store_requiere_codigo(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/monedas', ['nombre' => 'Sin código'])
            ->assertStatus(422);
    }

    public function test_store_admin_solo(): void
    {
        $this->actingAs($this->operador)
            ->postJson('/api/v1/monedas', ['codigo' => 'XXX', 'nombre' => 'Test'])
            ->assertForbidden();
    }

    public function test_show_muestra_moneda(): void
    {
        $moneda = Moneda::factory()->create();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/monedas/{$moneda->id}")
            ->assertOk()
            ->assertJsonPath('id', $moneda->id);
    }

    public function test_show_404_si_no_existe(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/monedas/99999')->assertNotFound();
    }

    public function test_update_modifica_moneda(): void
    {
        $moneda = Moneda::factory()->create(['nombre' => 'Original']);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/monedas/{$moneda->id}", ['nombre' => 'Modificado']);

        $response->assertOk()->assertJsonPath('nombre', 'Modificado');
    }

    public function test_update_operador_no_puede(): void
    {
        $moneda = Moneda::factory()->create();

        $this->actingAs($this->operador)
            ->patchJson("/api/v1/monedas/{$moneda->id}", ['codigo' => 'XXX'])
            ->assertForbidden();
    }

    public function test_destroy_elimina_moneda(): void
    {
        $moneda = Moneda::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/monedas/{$moneda->id}")
            ->assertNoContent();

        $this->assertModelMissing($moneda);
    }

    public function test_destroy_operador_no_puede(): void
    {
        $moneda = Moneda::factory()->create();

        $this->actingAs($this->operador)
            ->deleteJson("/api/v1/monedas/{$moneda->id}")
            ->assertForbidden();
    }
}
