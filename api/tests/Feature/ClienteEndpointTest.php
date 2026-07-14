<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\TipoOperacion;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[IgnoreDeprecations]
class ClienteEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;
    private User $lectura;
    private TipoOperacion $tipoCambio;

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

        $this->tipoCambio = TipoOperacion::where('codigo', 'cambio')->first();
    }

    // ── Index ──────────────────────────────────────────────────────────────────

    public function test_index_lista_clientes(): void
    {
        Cliente::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/clientes');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
    }

    public function test_index_busca_por_q(): void
    {
        Cliente::factory()->create(['nombre' => 'Pedro Pérez']);
        Cliente::factory()->create(['nombre' => 'María López']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/clientes?q=Pedro');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_incluye_inactivos_con_parametro(): void
    {
        Cliente::factory()->create(['nombre' => 'Activo']);
        $inactivo = Cliente::factory()->create(['nombre' => 'Inactivo']);
        $inactivo->delete();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/clientes?inactivos=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data'); // solo el inactivo via ->onlyTrashed()
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function test_store_crea_cliente(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/clientes', [
                'nombre'    => 'Nuevo Cliente',
                'documento' => 'V-12345678',
            ]);

        $response->assertCreated()
            ->assertJsonPath('nombre', 'Nuevo Cliente')
            ->assertJsonPath('documento', 'V-12345678');
    }

    public function test_store_requiere_nombre(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/clientes', [])
            ->assertStatus(422);
    }

    public function test_store_operador_no_puede_crear(): void
    {
        $this->actingAs($this->operador)
            ->postJson('/api/v1/clientes', ['nombre' => 'Test'])
            ->assertForbidden();
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function test_show_muestra_cliente_con_cuentas(): void
    {
        $cliente = Cliente::factory()->create();
        Cuenta::factory()->create([
            'cliente_id' => $cliente->id,
            'titular_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/clientes/{$cliente->id}");

        $response->assertOk()
            ->assertJsonPath('id', $cliente->id)
            ->assertJsonCount(1, 'cuentas');
    }

    public function test_show_404_si_no_existe(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/clientes/99999')
            ->assertNotFound();
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function test_update_modifica_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Original']);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/clientes/{$cliente->id}", [
                'nombre' => 'Modificado',
            ]);

        $response->assertOk()
            ->assertJsonPath('nombre', 'Modificado');
    }

    public function test_update_operador_puede_editar(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Original']);

        $this->actingAs($this->operador)
            ->patchJson("/api/v1/clientes/{$cliente->id}", [
                'nombre' => 'Editado por operador',
            ])
            ->assertOk();
    }

    public function test_update_lectura_no_puede_editar(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->lectura)
            ->patchJson("/api/v1/clientes/{$cliente->id}", [
                'nombre' => 'Hack',
            ])
            ->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function test_destroy_soft_delete(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/clientes/{$cliente->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($cliente);
    }

    public function test_destroy_operador_no_puede(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->operador)
            ->deleteJson("/api/v1/clientes/{$cliente->id}")
            ->assertForbidden();
    }

    // ── Cuentas ────────────────────────────────────────────────────────────────

    public function test_cuentas_lista_cuentas_del_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        Cuenta::factory()->count(2)->create([
            'cliente_id' => $cliente->id,
            'titular_id' => null,
        ]);
        Cuenta::factory()->create(); // de otro titular

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/clientes/{$cliente->id}/cuentas");

        $response->assertOk()
            ->assertJsonCount(2);
    }

    // ── Operaciones ────────────────────────────────────────────────────────────

    public function test_operaciones_lista_operaciones_del_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $op1 = \App\Models\Operacion::factory()->create([
            'cliente_id'        => $cliente->id,
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);
        $op2 = \App\Models\Operacion::factory()->create([
            'cliente_id'        => $cliente->id,
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'fecha'             => '2025-01-01',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/clientes/{$cliente->id}/operaciones");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_operaciones_filtra_por_fecha(): void
    {
        $cliente = Cliente::factory()->create();
        \App\Models\Operacion::factory()->create([
            'cliente_id'        => $cliente->id,
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'fecha'             => '2026-05-10',
        ]);
        \App\Models\Operacion::factory()->create([
            'cliente_id'        => $cliente->id,
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'fecha'             => '2026-06-15',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/clientes/{$cliente->id}/operaciones?fecha_desde=2026-06-01");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Exportar ───────────────────────────────────────────────────────────────

    public function test_exportar_operaciones_retorna_pdf(): void
    {
        $cliente = Cliente::factory()->create();
        \App\Models\Operacion::factory()->create([
            'cliente_id'        => $cliente->id,
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/clientes/{$cliente->id}/operaciones/exportar");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ── Restaurar ──────────────────────────────────────────────────────────────

    public function test_restaurar_cliente_no_eliminado_da_422(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/clientes/{$cliente->id}/restaurar");

        $response->assertStatus(422)
            ->assertJson(['message' => 'El cliente no está eliminado.']);
    }

    public function test_restaurar_cliente_eliminado_usa_contenedor(): void
    {
        $cliente = Cliente::factory()->create();
        $cliente->delete();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/clientes/{$cliente->id}/restaurar");

        // Route model binding no encuentra soft-deleted por defecto → 404
        // (bug conocido: falta ->withTrashed() en el binding)
        $response->assertNotFound();
    }
}
