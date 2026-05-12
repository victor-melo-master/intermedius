<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OperacionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;
    private User $contador;
    private User $lectura;
    private Moneda $usd;
    private Moneda $ves;
    private Cuenta $cuentaUsd;
    private Cuenta $cuentaVes;
    private TipoOperacion $tipoCambio;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->seed(CatalogosBaseSeeder::class);

        $this->usd = Moneda::where('codigo', 'USD')->first();
        $this->ves = Moneda::where('codigo', 'VES')->first();

        $titular = Titular::factory()->create();

        $this->cuentaUsd = Cuenta::factory()->create([
            'moneda_id'  => $this->usd->id,
            'titular_id' => $titular->id,
            'tipo'       => 'plataforma',
            'activa'     => true,
        ]);
        $this->cuentaVes = Cuenta::factory()->create([
            'moneda_id'  => $this->ves->id,
            'titular_id' => $titular->id,
            'tipo'       => 'banco',
            'activa'     => true,
        ]);

        $this->tipoCambio = TipoOperacion::where('codigo', 'cambio')->first();

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');

        $this->contador = User::factory()->create(['activo' => true]);
        $this->contador->assignRole('contador');

        $this->lectura = User::factory()->create(['activo' => true]);
        $this->lectura->assignRole('lectura');
    }

    private function payloadCambio(): array
    {
        return [
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'cambio',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $this->cuentaUsd->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $this->cuentaVes->id, 'monto' => 3650.0, 'tasa_a_usd' => round(1 / 36.50, 8)],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. index retorna operaciones paginadas
    // ─────────────────────────────────────────────────────────────────────────
    public function test_index_retorna_operaciones_paginadas(): void
    {
        Operacion::factory()->count(3)->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);

        $response = $this->actingAs($this->operador, 'sanctum')
            ->getJson('/api/v1/operaciones');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. store crea operación y retorna 201
    // ─────────────────────────────────────────────────────────────────────────
    public function test_store_crea_operacion_y_retorna_201(): void
    {
        $response = $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/operaciones', $this->payloadCambio());

        $response->assertCreated()
            ->assertJsonPath('data.estatus', 'sin_verificar')
            ->assertJsonStructure(['data' => ['id', 'fecha', 'ganancia', 'movimientos']]);

        $this->assertDatabaseCount('movimientos', 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. store falla sin autenticación
    // ─────────────────────────────────────────────────────────────────────────
    public function test_store_falla_sin_autenticacion(): void
    {
        $this->postJson('/api/v1/operaciones', $this->payloadCambio())
            ->assertUnauthorized();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. show eager loads movimientos
    // ─────────────────────────────────────────────────────────────────────────
    public function test_show_eager_loads_movimientos(): void
    {
        $response = $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/operaciones', $this->payloadCambio());

        $id = $response->json('data.id');

        $this->actingAs($this->operador, 'sanctum')
            ->getJson("/api/v1/operaciones/{$id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['movimientos']]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. verificar solo accesible para contador, admin, super_admin
    // ─────────────────────────────────────────────────────────────────────────
    public function test_verificar_solo_accesible_para_contador_admin_superadmin(): void
    {
        $response = $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/operaciones', $this->payloadCambio());
        $id = $response->json('data.id');

        // Operador NO puede verificar
        $this->actingAs($this->operador, 'sanctum')
            ->patchJson("/api/v1/operaciones/{$id}/verificar")
            ->assertForbidden();

        // Rol lectura tampoco
        $this->actingAs($this->lectura, 'sanctum')
            ->patchJson("/api/v1/operaciones/{$id}/verificar")
            ->assertForbidden();

        // Contador SÍ puede verificar
        $this->actingAs($this->contador, 'sanctum')
            ->patchJson("/api/v1/operaciones/{$id}/verificar")
            ->assertOk()
            ->assertJsonPath('data.estatus', 'verificado');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. destroy retorna 405
    // ─────────────────────────────────────────────────────────────────────────
    public function test_destroy_retorna_405(): void
    {
        $operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);

        $this->actingAs($this->operador, 'sanctum')
            ->deleteJson("/api/v1/operaciones/{$operacion->id}")
            ->assertStatus(405)
            ->assertJsonPath('message', 'Las operaciones no se eliminan. Use ajuste manual para corregir.');
    }
}
