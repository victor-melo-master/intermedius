<?php

namespace Tests\Feature;

use App\Models\Banco;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Titular;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuentaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['email' => 'operador@test.com']);
        $this->operador->assignRole('operador');
    }

    public function test_index_lista_cuentas(): void
    {
        Cuenta::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/cuentas');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/cuentas');
        $response->assertStatus(401);
    }

    public function test_index_filtra_por_titular(): void
    {
        $titular = Titular::factory()->create();
        Cuenta::factory()->count(2)->create(['titular_id' => $titular->id]);
        Cuenta::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/cuentas?titular_id=' . $titular->id);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_store_crea_cuenta(): void
    {
        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();

        $payload = [
            'alias'      => 'Mi Cuenta Test',
            'tipo'       => 'banco',
            'moneda_id'  => $moneda->id,
            'titular_id' => $titular->id,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/cuentas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('alias', 'Mi Cuenta Test')
            ->assertJsonPath('tipo', 'banco')
            ->assertJsonStructure(['id', 'alias', 'tipo', 'moneda']);
    }

    public function test_store_requiere_campos_minimos(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/cuentas', []);

        $response->assertStatus(422);
    }

    public function test_con_banco(): void
    {
        $banco = Banco::factory()->create();
        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();

        $payload = [
            'alias'      => 'Cuenta con Banco',
            'tipo'       => 'banco',
            'banco_id'   => $banco->id,
            'moneda_id'  => $moneda->id,
            'titular_id' => $titular->id,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/cuentas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('banco.id', $banco->id);
    }

    public function test_store_operador_solo_para_terceros(): void
    {
        $moneda = Moneda::factory()->create();
        $terceros = Titular::factory()->create(['alias' => 'terceros']);

        $payload = [
            'alias'      => 'Terceros Test',
            'tipo'       => 'banco',
            'moneda_id'  => $moneda->id,
            'titular_id' => $terceros->id,
        ];

        $response = $this->actingAs($this->operador)
            ->postJson('/api/v1/cuentas', $payload);

        $response->assertStatus(201);
    }

    public function test_store_operador_rechaza_otro_titular(): void
    {
        $moneda = Moneda::factory()->create();
        $otroTitular = Titular::factory()->create(['alias' => 'otro']);

        $payload = [
            'alias'      => 'No Terceros',
            'tipo'       => 'banco',
            'moneda_id'  => $moneda->id,
            'titular_id' => $otroTitular->id,
        ];

        $response = $this->actingAs($this->operador)
            ->postJson('/api/v1/cuentas', $payload);

        $response->assertStatus(403);
    }

    public function test_show_muestra_cuenta_con_relaciones(): void
    {
        $cuenta = Cuenta::factory()->banco()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/cuentas/{$cuenta->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $cuenta->id)
            ->assertJsonStructure(['id', 'alias', 'tipo', 'titular', 'banco', 'moneda']);
    }

    public function test_update_modifica_cuenta(): void
    {
        $cuenta = Cuenta::factory()->create(['alias' => 'Original']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/cuentas/{$cuenta->id}", [
                'alias'      => 'Modificado',
                'titular_id' => $cuenta->titular_id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('alias', 'Modificado');
    }

    public function test_update_requiere_admin(): void
    {
        $cuenta = Cuenta::factory()->create();

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/cuentas/{$cuenta->id}", [
                'alias'      => 'Hack',
                'titular_id' => $cuenta->titular_id,
            ]);

        $response->assertStatus(403);
    }

    public function test_destroy_elimina_cuenta(): void
    {
        $cuenta = Cuenta::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/cuentas/{$cuenta->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted($cuenta);
    }

    public function test_destroy_requiere_admin(): void
    {
        $cuenta = Cuenta::factory()->create();

        $response = $this->actingAs($this->operador)
            ->deleteJson("/api/v1/cuentas/{$cuenta->id}");

        $response->assertStatus(403);
    }

    public function test_cargar_saldo_actualiza_cache(): void
    {
        $cuenta = Cuenta::factory()->create(['saldo_cache' => 0]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/cuentas/{$cuenta->id}/saldo", [
                'saldo' => 1500.50,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('saldo_cache', '1500.5');
    }

    public function test_cargar_saldo_requiere_admin(): void
    {
        $cuenta = Cuenta::factory()->create();

        $response = $this->actingAs($this->operador)
            ->postJson("/api/v1/cuentas/{$cuenta->id}/saldo", [
                'saldo' => 100,
            ]);

        $response->assertStatus(403);
    }

    public function test_cargar_saldo_valida_numerico(): void
    {
        $cuenta = Cuenta::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/cuentas/{$cuenta->id}/saldo", [
                'saldo' => 'no-es-numero',
            ]);

        $response->assertStatus(422);
    }

    public function test_cargar_saldo_rechaza_negativo(): void
    {
        $cuenta = Cuenta::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/cuentas/{$cuenta->id}/saldo", [
                'saldo' => -100,
            ]);

        $response->assertStatus(422);
    }
}
