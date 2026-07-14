<?php

namespace Tests\Unit\Services\Pool;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use App\Services\Pool\PoolNotifier;
use App\Services\Pool\PoolService;
use App\Services\Pool\PoolValidator;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoolServiceTest extends TestCase
{
    use RefreshDatabase;

    private PoolService $service;
    private User $pagador;
    private User $admin;
    private TipoOperacion $tipoCambio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->pagador = User::factory()->create(['activo' => true]);
        $this->pagador->assignRole('pagador');

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->tipoCambio = TipoOperacion::where('codigo', 'cambio')->first();

        $validator = new PoolValidator();
        $notifier = $this->createMock(PoolNotifier::class);
        $this->service = new PoolService($validator, $notifier);
    }

    private function crearOperacionEnEspera(): Operacion
    {
        return Operacion::factory()->create([
            'estado' => 'en_espera',
            'estado_pool' => 'pendiente',
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id' => $this->pagador->id,
        ]);
    }

    public function test_tomar_operaciones_asigna_operaciones_al_pagador()
    {
        $op1 = $this->crearOperacionEnEspera();
        $op2 = $this->crearOperacionEnEspera();

        Event::fake();

        $resultado = $this->service->tomarOperaciones($this->pagador, 5);

        $this->assertCount(2, $resultado);

        $op1->refresh();
        $op2->refresh();
        $this->assertEquals('asignada', $op1->estado_pool);
        $this->assertEquals('en_proceso', $op1->estado);
        $this->assertEquals($this->pagador->id, $op1->pagador_id);
        $this->assertNotNull($op1->asignada_at);
        $this->assertEquals('asignada', $op2->estado_pool);
    }

    public function test_tomar_operaciones_lanza_excepcion_sin_permiso()
    {
        $this->expectException(ValidationException::class);

        $userSinPermiso = User::factory()->create(['activo' => true]);
        $userSinPermiso->assignRole('lectura');

        $this->service->tomarOperaciones($userSinPermiso, 5);
    }

    public function test_tomar_operaciones_solo_toma_operaciones_pendientes()
    {
        $opPendiente = $this->crearOperacionEnEspera();
        $opAsignada = Operacion::factory()->create([
            'estado' => 'en_proceso',
            'estado_pool' => 'asignada',
            'tipo_operacion_id' => $this->tipoCambio->id,
            'operador_id' => $this->pagador->id,
            'pagador_id' => $this->pagador->id,
        ]);

        $resultado = $this->service->tomarOperaciones($this->pagador, 5);

        $this->assertCount(1, $resultado);
        $this->assertEquals($opPendiente->id, $resultado->first()->id);
    }

    public function test_soltar_operaciones_devuelve_operaciones_al_pool()
    {
        Event::fake();

        $this->crearOperacionEnEspera();
        $asignadas = $this->service->tomarOperaciones($this->pagador, 5);
        $op = $asignadas->first();

        $this->service->soltarOperaciones(collect([$op]), $this->pagador);

        $op->refresh();
        $this->assertEquals('pendiente', $op->estado_pool);
        $this->assertEquals('en_espera', $op->estado);
        $this->assertNull($op->pagador_id);
    }

    public function test_pagar_operacion_marca_como_pagada_y_crea_movimientos()
    {
        Event::fake();

        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuentaOrigen = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 1000,
            'saldo_cache_at' => now(),
        ]);
        $cuentaDestino = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
        ]);

        $this->crearOperacionEnEspera();
        $asignadas = $this->service->tomarOperaciones($this->pagador, 5);
        $op = $asignadas->first();

        $op->transacciones()->create([
            'cuenta_origen_id' => $cuentaOrigen->id,
            'cuenta_destino_id' => $cuentaDestino->id,
            'moneda_id' => $moneda->id,
            'monto' => 100,
            'estado' => 'validada',
            'orden' => 1,
        ]);

        $this->service->pagarOperacion($op, $this->pagador);

        $op->refresh();
        $this->assertEquals('pagada', $op->estado_pool);
        $this->assertEquals('concluida', $op->estado);
        $this->assertNotNull($op->pagada_at);

        $this->assertDatabaseHas('movimientos', [
            'operacion_id' => $op->id,
            'cuenta_id' => $cuentaOrigen->id,
            'monto' => -100,
        ]);
        $this->assertDatabaseHas('movimientos', [
            'operacion_id' => $op->id,
            'cuenta_id' => $cuentaDestino->id,
            'monto' => 100,
        ]);
    }

    public function test_cancelar_operacion_cancela_transacciones_pendientes()
    {
        Event::fake();

        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuentaOrigen = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 1000,
            'saldo_cache_at' => now(),
        ]);
        $cuentaDestino = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
        ]);

        $op = $this->crearOperacionEnEspera();
        $this->service->tomarOperaciones($this->pagador, 5);

        $tx = $op->transacciones()->create([
            'cuenta_origen_id' => $cuentaOrigen->id,
            'cuenta_destino_id' => $cuentaDestino->id,
            'moneda_id' => $moneda->id,
            'monto' => 100,
            'estado' => 'pendiente',
            'orden' => 1,
        ]);

        $this->service->cancelarOperacion($op, $this->admin, 'Cancelado por el admin');

        $op->refresh();
        $this->assertEquals('cancelada', $op->estado_pool);
        $this->assertEquals('cancelada', $op->estado);
        $this->assertNotNull($op->cancelada_at);
        $this->assertEquals('Cancelado por el admin', $op->motivo_cancelacion);

        $tx->refresh();
        $this->assertEquals('cancelada', $tx->estado);
    }
}
