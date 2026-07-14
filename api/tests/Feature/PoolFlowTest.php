<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use App\Jobs\VerificarSlaPoolJob;
use App\Services\Pool\PoolNotifier;
use App\Services\Pool\PoolService;
use App\Services\Transaccion\TransaccionService;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoolFlowTest extends TestCase
{
    use RefreshDatabase;

    private PoolService $poolService;
    private TransaccionService $transaccionService;
    private User $pagador;
    private User $operador;
    private User $admin;
    private Moneda $usd;
    private Cuenta $cuentaOrigen;
    private Cuenta $cuentaDestino;
    private TipoOperacion $tipoOperacion;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->tipoOperacion = TipoOperacion::where('codigo', 'cambio')->first();

        $this->pagador = User::factory()->create(['activo' => true]);
        $this->pagador->assignRole('pagador');

        $this->operador = User::factory()->create(['activo' => true]);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->poolService = $this->app->make(PoolService::class);
        $this->transaccionService = $this->app->make(TransaccionService::class);
    }

    /** @test */
    public function flujo_completo_pool(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoOperacion->id,
            'operador_id'        => $this->operador->id,
            'estado'             => 'en_espera',
            'estado_pool'        => 'pendiente',
            'descripcion'        => 'Test flujo completo pool',
        ]);

        $transacciones = $this->transaccionService->crearTransacciones($operacion, [[
            'cuenta_origen_id'  => $this->cuentaOrigen->id,
            'cuenta_destino_id' => $this->cuentaDestino->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 100,
        ]]);

        $this->assertDatabaseCount('transacciones', 1);
        $this->assertEquals('pendiente', $transacciones->first()->estado);

        $tomadas = $this->poolService->tomarOperaciones($this->pagador, 1);

        $this->assertCount(1, $tomadas);
        $this->assertEquals('en_proceso', $operacion->fresh()->estado);
        $this->assertEquals('asignada', $operacion->fresh()->estado_pool);

        $operacion = $operacion->fresh();
        $transaccion = $operacion->transacciones()->first();
        $this->transaccionService->validarTransaccion($transaccion, $this->pagador);

        $this->assertEquals('validada', $transaccion->fresh()->estado);

        $this->poolService->pagarOperacion($operacion, $this->pagador);

        $this->assertEquals('concluida', $operacion->fresh()->estado);
        $this->assertEquals('pagada', $operacion->fresh()->estado_pool);
    }

    /** @test */
    public function no_se_puede_pagar_sin_validar_transacciones(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoOperacion->id,
            'operador_id'        => $this->operador->id,
            'estado'             => 'en_espera',
            'estado_pool'        => 'pendiente',
            'descripcion'        => 'Test sin validar',
        ]);

        $this->transaccionService->crearTransacciones($operacion, [[
            'cuenta_origen_id'  => $this->cuentaOrigen->id,
            'cuenta_destino_id' => $this->cuentaDestino->id,
            'moneda_id'         => $this->usd->id,
            'monto'             => 100,
        ]]);

        $this->poolService->tomarOperaciones($this->pagador, 1);

        $this->expectException(ValidationException::class);
        $this->poolService->pagarOperacion($operacion, $this->pagador);
    }

    /** @test */
    public function operador_sin_permisos_no_puede_tomar_operaciones(): void
    {
        $this->expectException(ValidationException::class);

        $this->poolService->tomarOperaciones($this->operador, 1);
    }

    /** @test */
    public function admin_puede_cancelar_operacion(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now(),
            'tipo_operacion_id'  => $this->tipoOperacion->id,
            'operador_id'        => $this->operador->id,
            'estado'             => 'en_espera',
            'estado_pool'        => 'pendiente',
            'descripcion'        => 'Test cancelación',
        ]);

        $this->poolService->cancelarOperacion($operacion, $this->admin, 'Prueba de cancelación');

        $this->assertEquals('cancelada', $operacion->fresh()->estado);
        $this->assertEquals('cancelada', $operacion->fresh()->estado_pool);
        $this->assertNotNull($operacion->fresh()->cancelada_at);
        $this->assertEquals('Prueba de cancelación', $operacion->fresh()->motivo_cancelacion);
    }

    /** @test */
    public function sla_job_detecta_operaciones_en_espera(): void
    {
        $operacion = Operacion::create([
            'fecha'              => now()->subDays(1),
            'tipo_operacion_id'  => $this->tipoOperacion->id,
            'operador_id'        => $this->operador->id,
            'estado'             => 'en_espera',
            'estado_pool'        => 'pendiente',
            'descripcion'        => 'SLA test',
        ]);

        $operacion->created_at = now()->subMinutes(10);
        $operacion->save();

        $job = new VerificarSlaPoolJob(1);

        $notifierMock = $this->createMock(PoolNotifier::class);
        $notifierMock->expects($this->once())
            ->method('slaExcedida')
            ->with($this->callback(function (Operacion $op) use ($operacion) {
                return $op->id === $operacion->id;
            }), $this->greaterThanOrEqual(1));

        $job->handle($notifierMock);
    }
}
