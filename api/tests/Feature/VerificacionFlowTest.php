<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\Transaccion;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VerificacionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;
    private User $contador;
    private User $admin;
    private Moneda $usd;
    private Cuenta $cuentaOrigen;
    private Cuenta $cuentaDestino;
    private Cuenta $cuentaOrigen2;
    private Cuenta $cuentaDestino2;
    private TipoOperacion $tipoCambio;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->seed(CatalogosBaseSeeder::class);

        $this->usd = Moneda::where('codigo', 'USD')->first();
        $titular = Titular::factory()->create();

        $this->cuentaOrigen = Cuenta::factory()->create([
            'moneda_id'     => $this->usd->id,
            'titular_id'    => $titular->id,
            'tipo'          => 'banco',
            'saldo_cache'   => 1000,
            'saldo_cache_at' => now(),
            'activa'        => true,
        ]);
        $this->cuentaDestino = Cuenta::factory()->create([
            'moneda_id'     => $this->usd->id,
            'titular_id'    => $titular->id,
            'tipo'          => 'banco',
            'saldo_cache'   => 0,
            'activa'        => true,
        ]);
        $this->cuentaOrigen2 = Cuenta::factory()->create([
            'moneda_id'     => $this->usd->id,
            'titular_id'    => $titular->id,
            'tipo'          => 'banco',
            'saldo_cache'   => 500,
            'saldo_cache_at' => now(),
            'activa'        => true,
        ]);
        $this->cuentaDestino2 = Cuenta::factory()->create([
            'moneda_id'     => $this->usd->id,
            'titular_id'    => $titular->id,
            'tipo'          => 'banco',
            'saldo_cache'   => 0,
            'activa'        => true,
        ]);

        $this->tipoCambio = TipoOperacion::where('codigo', 'cambio')->first();

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');

        $this->contador = User::factory()->create(['activo' => true]);
        $this->contador->assignRole('contador');

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');
    }

    private function crearOperacionConTransaccion(): array
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'sin_verificar',
            'estado_pool'        => 'pendiente',
        ]);

        $transaccion = Transaccion::create([
            'operacion_id'      => $operacion->id,
            'cuenta_origen_id'  => $this->cuentaOrigen->id,
            'cuenta_destino_id' => $this->cuentaDestino->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 100,
            'estado'            => 'pendiente',
            'orden'             => 1,
        ]);

        return ['operacion' => $operacion, 'transaccion' => $transaccion];
    }

    // ── Iniciar Verificación ──────────────────────────────────────────────────

    public function test_iniciar_verificacion_cambia_estatus(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'sin_verificar',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->contador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/iniciar-verificacion");

        $response->assertOk()
            ->assertJsonPath('operacion.estatus', 'en_verificacion');

        $this->assertEquals('en_verificacion', $operacion->fresh()->estatus);
    }

    public function test_iniciar_verificacion_requiere_permiso(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'sin_verificar',
            'estado_pool'        => 'pendiente',
        ]);

        $lectura = User::factory()->create(['activo' => true]);
        $lectura->assignRole('lectura');

        $response = $this->actingAs($lectura)
            ->postJson("/api/v1/operaciones/{$operacion->id}/iniciar-verificacion");

        $response->assertForbidden();
    }

    public function test_iniciar_verificacion_ya_verificada_da_422(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'verificado',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->contador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/iniciar-verificacion");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en estado sin_verificar.');
    }

    // ── GET Verificación ──────────────────────────────────────────────────────

    public function test_verificacion_retorna_operacion_y_saldos(): void
    {
        ['operacion' => $operacion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $response = $this->actingAs($this->contador)
            ->getJson("/api/v1/operaciones/{$operacion->id}/verificacion");

        $response->assertOk()
            ->assertJsonStructure([
                'operacion',
                'saldos',
                'total_movimientos',
                'movimientos_validados',
            ])
            ->assertJsonPath('total_movimientos', 0)
            ->assertJsonPath('movimientos_validados', 0);
    }

    public function test_verificacion_requiere_autenticacion(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
        ]);

        $response = $this->getJson("/api/v1/operaciones/{$operacion->id}/verificacion");

        $response->assertUnauthorized();
    }

    public function test_verificacion_saldos_incluyen_cuentas_del_contexto(): void
    {
        ['operacion' => $operacion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $response = $this->actingAs($this->contador)
            ->getJson("/api/v1/operaciones/{$operacion->id}/verificacion");

        $saldos = $response->json('saldos');
        $this->assertArrayHasKey($this->cuentaOrigen->id, $saldos);
        $this->assertArrayHasKey($this->cuentaDestino->id, $saldos);
    }

    // ── Agregar Transacción ───────────────────────────────────────────────────

    public function test_agregar_transaccion_durante_verificacion(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->operador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
                'cuenta_origen_id'  => $this->cuentaOrigen2->id,
                'cuenta_destino_id' => $this->cuentaDestino2->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 50,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('estado', 'pendiente')
            ->assertJsonPath('monto', '50.00');

        $this->assertDatabaseCount('transacciones', 1);
    }

    public function test_agregar_transaccion_fuera_verificacion_da_422(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'sin_verificar',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->operador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
                'cuenta_origen_id'  => $this->cuentaOrigen2->id,
                'cuenta_destino_id' => $this->cuentaDestino2->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en un estado que permita agregar transacciones.');
    }

    public function test_agregar_transaccion_moneda_no_coincide_da_422(): void
    {
        $ves = Moneda::where('codigo', 'VES')->first();
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->operador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
                'cuenta_origen_id'  => $this->cuentaOrigen2->id,
                'cuenta_destino_id' => $this->cuentaDestino2->id,
                'moneda_id'         => $ves->id,
                'monto'             => 50,
            ]);

        $response->assertStatus(422);
    }

    public function test_agregar_transaccion_saldo_insuficiente_no_falla_en_creacion(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->operador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
                'cuenta_origen_id'  => $this->cuentaOrigen2->id,
                'cuenta_destino_id' => $this->cuentaDestino2->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 999999,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('estado', 'pendiente');
    }

    public function test_agregar_transaccion_requiere_autenticacion(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
        ]);

        $response = $this->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
            'cuenta_origen_id'  => $this->cuentaOrigen2->id,
            'cuenta_destino_id' => $this->cuentaDestino2->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 50,
        ]);

        $response->assertUnauthorized();
    }

    // ── Editar Transacción (cambiar cuentas) ──────────────────────────────────

    public function test_editar_transaccion_cambia_cuenta_destino(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}", [
                'cuenta_destino_id' => $this->cuentaDestino2->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('cuenta_destino_id', $this->cuentaDestino2->id);

        $this->assertEquals($this->cuentaDestino2->id, $transaccion->fresh()->cuenta_destino_id);
    }

    public function test_editar_transaccion_cambia_cuenta_origen(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}", [
                'cuenta_origen_id' => $this->cuentaOrigen2->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('cuenta_origen_id', $this->cuentaOrigen2->id);
    }

    public function test_editar_transaccion_moneda_no_coincide_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $ves = Moneda::where('codigo', 'VES')->first();
        $cuentaVes = Cuenta::factory()->create([
            'moneda_id'  => $ves->id,
            'titular_id' => Titular::factory()->create()->id,
            'tipo'       => 'banco',
            'activa'     => true,
        ]);

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}", [
                'cuenta_origen_id' => $cuentaVes->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La cuenta de origen debe ser de la misma moneda que la transacción.');
    }

    public function test_editar_transaccion_fuera_verificacion_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}", [
                'cuenta_destino_id' => $this->cuentaDestino2->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en un estado que permita editar transacciones.');
    }

    public function test_editar_transaccion_validada_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);
        $transaccion->update(['estado' => 'validada']);

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}", [
                'cuenta_destino_id' => $this->cuentaDestino2->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Solo se pueden editar transacciones en estado pendiente.');
    }

    public function test_editar_transaccion_no_pertenece_a_operacion_da_404(): void
    {
        ['operacion' => $operacion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $otraTransaccion = Transaccion::create([
            'operacion_id'      => $operacion->id,
            'cuenta_origen_id'  => $this->cuentaOrigen->id,
            'cuenta_destino_id' => $this->cuentaDestino->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 100,
            'estado'            => 'pendiente',
            'orden'             => 2,
        ]);

        $otraOperacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$otraOperacion->id}/transacciones/{$otraTransaccion->id}", [
                'cuenta_destino_id' => $this->cuentaDestino2->id,
            ]);

        $response->assertStatus(404);
    }

    // ── Validar Transacción ───────────────────────────────────────────────────

    public function test_validar_transaccion_cambia_estado(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}/validar");

        $response->assertOk()
            ->assertJsonPath('transaccion.estado', 'validada')
            ->assertJsonPath('todas_validadas', true);

        $this->assertEquals('validada', $transaccion->fresh()->estado);
    }

    public function test_validar_transaccion_todas_validadas_false(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        Transaccion::create([
            'operacion_id'      => $operacion->id,
            'cuenta_origen_id'  => $this->cuentaOrigen2->id,
            'cuenta_destino_id' => $this->cuentaDestino2->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 50,
            'estado'            => 'pendiente',
            'orden'             => 2,
        ]);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}/validar");

        $response->assertOk()
            ->assertJsonPath('todas_validadas', false);
    }

    public function test_validar_transaccion_fuera_verificacion_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}/validar");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en proceso de verificación.');
    }

    public function test_validar_transaccion_ya_validada_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);
        $transaccion->update(['estado' => 'validada']);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}/validar");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Solo se pueden validar transacciones en estado pendiente.');
    }

    public function test_validar_transaccion_no_pertenece_a_operacion_da_404(): void
    {
        ['operacion' => $operacion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $otraOperacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$otraOperacion->id}/transacciones/{$operacion->transacciones->first()->id}/validar");

        $response->assertStatus(404);
    }

    // ── Eliminar Transacción ──────────────────────────────────────────────────

    public function test_eliminar_transaccion_pendiente(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $response = $this->actingAs($this->operador)
            ->deleteJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('transacciones', ['id' => $transaccion->id]);
    }

    public function test_eliminar_transaccion_validada_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);
        $transaccion->update(['estado' => 'validada']);

        $response = $this->actingAs($this->operador)
            ->deleteJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Solo se pueden eliminar transacciones en estado pendiente.');
    }

    public function test_eliminar_transaccion_fuera_verificacion_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();

        $response = $this->actingAs($this->operador)
            ->deleteJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en un estado que permita eliminar transacciones.');
    }

    // ── Cerrar Verificación ───────────────────────────────────────────────────

    public function test_cerrar_verificacion_todas_validadas(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);
        $transaccion->update(['estado' => 'validada']);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar");

        $response->assertOk()
            ->assertJsonPath('data.estatus', 'verificado');

        $this->assertEquals('verificado', $operacion->fresh()->estatus);
        $this->assertNotNull($operacion->fresh()->verificado_at);
        $this->assertEquals($this->contador->id, $operacion->fresh()->verificado_por_id);
    }

    public function test_cerrar_verificacion_con_pendientes_da_422(): void
    {
        ['operacion' => $operacion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        \App\Models\Movimiento::create([
            'operacion_id'          => $operacion->id,
            'cuenta_id'             => $this->cuentaOrigen->id,
            'moneda_id'             => $this->usd->id,
            'monto'                 => -100,
            'tasa_a_usd'            => 1.0,
            'monto_usd_equivalente' => -100,
            'orden'                 => 1,
        ]);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Hay 1 movimiento(s) sin validar. Todos deben estar validados para cerrar la verificación.')
            ->assertJsonPath('movimientos_pendientes', 1);
    }

    public function test_cerrar_verificacion_sin_iniciar_da_422(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'sin_verificar',
            'estado_pool'        => 'pendiente',
        ]);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en proceso de verificación.');
    }

    public function test_cerrar_verificacion_ya_cerrada_da_422(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update([
            'estatus'           => 'verificado',
            'verificado_at'     => now(),
            'verificado_por_id' => $this->contador->id,
        ]);

        $response = $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La operación no está en proceso de verificación.');
    }

    // ── Flujo Completo ────────────────────────────────────────────────────────

    public function test_flujo_completo_verificacion(): void
    {
        // 1. Crear operación
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'sin_verificar',
            'estado_pool'        => 'pendiente',
        ]);

        $tx1 = Transaccion::create([
            'operacion_id'      => $operacion->id,
            'cuenta_origen_id'  => $this->cuentaOrigen->id,
            'cuenta_destino_id' => $this->cuentaDestino->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 100,
            'estado'            => 'pendiente',
            'orden'             => 1,
        ]);

        // 2. Iniciar verificación
        $this->actingAs($this->contador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/iniciar-verificacion")
            ->assertOk();

        // 3. Verificar vista
        $this->actingAs($this->contador)
            ->getJson("/api/v1/operaciones/{$operacion->id}/verificacion")
            ->assertOk()
            ->assertJsonPath('total_transacciones', 1);

        // 4. Cambiar cuenta destino de tx1
        $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$tx1->id}", [
                'cuenta_destino_id' => $this->cuentaDestino2->id,
            ])
            ->assertOk();

        // 5. Agregar tx2
        $this->actingAs($this->operador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
                'cuenta_origen_id'  => $this->cuentaOrigen2->id,
                'cuenta_destino_id' => $this->cuentaDestino2->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 50,
            ])
            ->assertStatus(201);

        // 6. Validar tx1
        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$tx1->id}/validar")
            ->assertOk()
            ->assertJsonPath('todas_validadas', false);

        // 7. Validar tx2
        $tx2 = $operacion->transacciones()->where('orden', 2)->first();
        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$tx2->id}/validar")
            ->assertOk()
            ->assertJsonPath('todas_validadas', true);

        // 8. Cerrar verificación
        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar")
            ->assertOk()
            ->assertJsonPath('data.estatus', 'verificado');

        // Verificar estado final
        $this->assertEquals('verificado', $operacion->fresh()->estatus);
        $this->assertEquals('validada', $tx1->fresh()->estado);
        $this->assertEquals('validada', $tx2->fresh()->estado);
    }

    // ── Auditoría ─────────────────────────────────────────────────────────────

    public function test_auditoria_registra_cambio_cuenta(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $this->actingAs($this->operador)
            ->putJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}", [
                'cuenta_destino_id' => $this->cuentaDestino2->id,
            ]);

        $this->assertDatabaseHas('activity_log', [
            'event'         => 'transaccion_modificada',
            'causer_id'     => $this->operador->id,
        ]);
    }

    public function test_auditoria_registra_transaccion_agregada(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoCambio->id,
            'operador_id'        => $this->operador->id,
            'estatus'            => 'en_verificacion',
            'estado_pool'        => 'pendiente',
        ]);

        $this->actingAs($this->operador)
            ->postJson("/api/v1/operaciones/{$operacion->id}/transacciones", [
                'cuenta_origen_id'  => $this->cuentaOrigen2->id,
                'cuenta_destino_id' => $this->cuentaDestino2->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 50,
            ]);

        $this->assertDatabaseHas('activity_log', [
            'event'     => 'transaccion_agregada',
            'causer_id' => $this->operador->id,
        ]);
    }

    public function test_auditoria_registra_transaccion_validada(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);

        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/transacciones/{$transaccion->id}/validar");

        $this->assertDatabaseHas('activity_log', [
            'event'     => 'transaccion_validada',
            'causer_id' => $this->contador->id,
        ]);
    }

    public function test_auditoria_registra_verificacion_cerrada(): void
    {
        ['operacion' => $operacion, 'transaccion' => $transaccion] = $this->crearOperacionConTransaccion();
        $operacion->update(['estatus' => 'en_verificacion']);
        $transaccion->update(['estado' => 'validada']);

        $this->actingAs($this->contador)
            ->patchJson("/api/v1/operaciones/{$operacion->id}/verificar");

        $this->assertDatabaseHas('activity_log', [
            'event'     => 'verificacion_completada',
            'causer_id' => $this->contador->id,
        ]);
    }
}
