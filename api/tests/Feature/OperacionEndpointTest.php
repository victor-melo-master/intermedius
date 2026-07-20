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
    private User $admin;
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

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');
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

    private function crearOperacion(): Operacion
    {
        $response = $this->actingAs($this->operador)
            ->postJson('/api/v1/operaciones', $this->payloadCambio());
        return Operacion::find($response->json('data.id'));
    }

    // ── Index ──────────────────────────────────────────────────────────────────

    public function test_index_retorna_operaciones_paginadas(): void
    {
        Operacion::factory()->count(3)->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);

        $response = $this->actingAs($this->operador)
            ->getJson('/api/v1/operaciones');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/operaciones')
            ->assertUnauthorized();
    }

    public function test_index_filtra_por_tipo_codigo(): void
    {
        $otroTipo = TipoOperacion::factory()->create(['codigo' => 'gasto']);
        Operacion::factory()->create(['tipo_operacion_id' => $this->tipoCambio->id, 'operador_id' => $this->operador->id]);
        Operacion::factory()->create(['tipo_operacion_id' => $otroTipo->id, 'operador_id' => $this->operador->id]);

        $response = $this->actingAs($this->operador)
            ->getJson('/api/v1/operaciones?tipo_codigo=cambio');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filtra_por_fecha(): void
    {
        Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'fecha'             => '2026-05-10',
        ]);
        Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'fecha'             => '2026-06-15',
        ]);

        $response = $this->actingAs($this->operador)
            ->getJson('/api/v1/operaciones?fecha_desde=2026-06-01&fecha_hasta=2026-06-30');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filtra_por_estatus(): void
    {
        Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'estatus'           => 'sin_verificar',
        ]);
        Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
            'estatus'           => 'verificado',
        ]);

        $response = $this->actingAs($this->operador)
            ->getJson('/api/v1/operaciones?estatus=verificado');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filtra_por_cuenta_id(): void
    {
        $otraCuenta = Cuenta::factory()->create([
            'moneda_id'  => $this->usd->id,
            'titular_id' => Titular::factory()->create()->id,
        ]);

        $op1 = Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);
        $op1->movimientos()->create([
            'cuenta_id'             => $this->cuentaUsd->id,
            'moneda_id'             => $this->usd->id,
            'monto'                 => -100,
            'tasa_a_usd'            => 1,
            'monto_usd_equivalente' => -100,
            'orden'                 => 1,
        ]);

        $op2 = Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);
        $op2->movimientos()->create([
            'cuenta_id'             => $otraCuenta->id,
            'moneda_id'             => $this->usd->id,
            'monto'                 => -50,
            'tasa_a_usd'            => 1,
            'monto_usd_equivalente' => -50,
            'orden'                 => 1,
        ]);

        $response = $this->actingAs($this->operador)
            ->getJson('/api/v1/operaciones?cuenta_id=' . $this->cuentaUsd->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function test_store_crea_operacion_y_retorna_201(): void
    {
        $response = $this->actingAs($this->operador)
            ->postJson('/api/v1/operaciones', $this->payloadCambio());

        $response->assertCreated()
            ->assertJsonPath('data.estatus', 'sin_verificar')
            ->assertJsonStructure(['data' => ['id', 'fecha', 'ganancia', 'movimientos']]);

        $this->assertDatabaseCount('movimientos', 2);
    }

    public function test_store_falla_sin_autenticacion(): void
    {
        $this->postJson('/api/v1/operaciones', $this->payloadCambio())
            ->assertUnauthorized();
    }

    public function test_store_valida_fecha_requerida(): void
    {
        $payload = $this->payloadCambio();
        unset($payload['fecha']);

        $this->actingAs($this->operador)
            ->postJson('/api/v1/operaciones', $payload)
            ->assertStatus(422);
    }

    public function test_store_valida_movimientos_requeridos(): void
    {
        $payload = $this->payloadCambio();
        unset($payload['movimientos']);

        $this->actingAs($this->operador)
            ->postJson('/api/v1/operaciones', $payload)
            ->assertStatus(422);
    }

    public function test_store_valida_tipo_codigo_existente(): void
    {
        $payload = $this->payloadCambio();
        $payload['tipo_codigo'] = 'no_existe';

        $this->actingAs($this->operador)
            ->postJson('/api/v1/operaciones', $payload)
            ->assertStatus(422);
    }

    public function test_store_lectura_no_puede_crear(): void
    {
        $this->actingAs($this->lectura)
            ->postJson('/api/v1/operaciones', $this->payloadCambio())
            ->assertForbidden();
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function test_show_eager_loads_movimientos(): void
    {
        $operacion = $this->crearOperacion();

        $this->actingAs($this->operador)
            ->getJson("/api/v1/operaciones/{$operacion->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['movimientos']]);
    }

    public function test_show_requiere_autenticacion(): void
    {
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $tipo->id,
            'operador_id'       => $this->operador->id,
        ]);

        $this->getJson("/api/v1/operaciones/{$operacion->id}")
            ->assertUnauthorized();
    }

    public function test_show_404_si_no_existe(): void
    {
        $this->actingAs($this->operador)
            ->getJson('/api/v1/operaciones/99999')
            ->assertNotFound();
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function test_update_modifica_operacion(): void
    {
        $operacion = $this->crearOperacion();

        $response = $this->actingAs($this->operador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}", [
                'referencia'    => 'NUEVOREF-001',
                'motivo_edicion' => 'Actualización de referencia.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.referencia', 'NUEVOREF-001');
    }

    public function test_update_requiere_motivo_edicion(): void
    {
        $operacion = $this->crearOperacion();

        $this->actingAs($this->operador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}", [
                'referencia' => 'NUEVOREF',
            ])
            ->assertStatus(422);
    }

    public function test_update_requiere_autenticacion(): void
    {
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $tipo->id,
            'operador_id'       => $this->operador->id,
        ]);

        $this->patchJson("/api/v1/operaciones/{$operacion->id}", [
            'referencia'    => 'test',
            'motivo_edicion' => 'test',
        ])->assertUnauthorized();
    }

    public function test_update_lectura_no_puede_editar(): void
    {
        $operacion = $this->crearOperacion();

        $this->actingAs($this->lectura)
            ->patchJson("/api/v1/operaciones/{$operacion->id}", [
                'referencia'    => 'test',
                'motivo_edicion' => 'test',
            ])->assertForbidden();
    }

    public function test_update_operacion_verificada_bloqueada_para_operador(): void
    {
        $operacion = $this->crearOperacion();
        $operacion->update([
            'estatus'           => 'verificado',
            'verificado_at'     => now(),
            'verificado_por_id' => $this->contador->id,
        ]);

        $response = $this->actingAs($this->operador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}", [
                'referencia'     => 'test',
                'motivo_edicion' => 'test',
            ]);

        $response->assertStatus(422);
    }

    public function test_super_admin_puede_editar_verificada(): void
    {
        $superAdmin = User::factory()->create(['activo' => true]);
        $superAdmin->assignRole('super_admin');

        $operacion = $this->crearOperacion();
        $operacion->update([
            'estatus'           => 'verificado',
            'verificado_at'     => now(),
            'verificado_por_id' => $this->contador->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/v1/operaciones/{$operacion->id}", [
                'referencia'     => 'SUPER-EDIT',
                'motivo_edicion' => 'Super admin corrige.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.referencia', 'SUPER-EDIT');
    }

    // ── Verificar ──────────────────────────────────────────────────────────────

    public function test_verificar_solo_accesible_para_contador_admin_superadmin(): void
    {
        $operacion = $this->crearOperacion();

        $this->actingAs($this->operador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar")
            ->assertForbidden();

        $this->actingAs($this->lectura)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar")
            ->assertForbidden();

        $this->actingAs($this->contador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/iniciar-verificacion")
            ->assertOk();

        foreach ($operacion->movimientos as $movimiento) {
            $this->actingAs($this->contador)
                ->patchJson("/api/v1/operaciones/{$operacion->id}/movimientos/{$movimiento->id}/validar")
                ->assertOk();
        }

        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar")
            ->assertOk()
            ->assertJsonPath('data.estatus', 'verificado');
    }

    public function test_verificar_requiere_autenticacion(): void
    {
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $tipo->id,
            'operador_id'       => $this->operador->id,
        ]);

        $this->patchJson("/api/v1/operaciones/{$operacion->id}/verificar")
            ->assertUnauthorized();
    }

    public function test_verificar_ya_verificado_da_422(): void
    {
        $operacion = $this->crearOperacion();
        $operacion->update([
            'estatus'           => 'verificado',
            'verificado_at'     => now(),
            'verificado_por_id' => $this->contador->id,
        ]);

        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar")
            ->assertStatus(422)
            ->assertJson(['message' => 'La operación no está en proceso de verificación.']);
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function test_destroy_retorna_405(): void
    {
        $operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);

        $this->actingAs($this->operador)
            ->deleteJson("/api/v1/operaciones/{$operacion->id}")
            ->assertStatus(405)
            ->assertJsonPath('message', 'Las operaciones no se eliminan. Use ajuste manual para corregir.');
    }
}
