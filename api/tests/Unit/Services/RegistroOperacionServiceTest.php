<?php

namespace Tests\Unit\Services;

use App\Jobs\ProcesarFifoOperacionJob;
use App\Jobs\RecalcularSaldoCuentaJob;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\Transaccion;
use App\Models\User;
use App\Services\Operaciones\RegistroOperacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistroOperacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistroOperacionService $service;
    private Moneda $usd;
    private Moneda $ves;
    private Titular $titular;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usd      = Moneda::factory()->usd()->create();
        $this->ves      = Moneda::factory()->ves()->create();
        $this->titular  = Titular::factory()->create();
        $this->operador = User::factory()->create();
        $this->service  = $this->app->make(RegistroOperacionService::class);
    }

    private function crearTasaDiariaUsdVes(float $compra = 36.20, float $venta = 36.50): TasaDiaria
    {
        return TasaDiaria::create([
            'fecha'              => now()->toDateString(),
            'moneda_base_id'     => $this->usd->id,
            'moneda_cotizada_id' => $this->ves->id,
            'tasa_compra'        => $compra,
            'tasa_venta'         => $venta,
            'definida_por_id'    => $this->operador->id,
            'notas'              => null,
            'vigente_desde'      => now()->subMinute(),
            'vigente_hasta'      => null,
        ]);
    }

    private function cuentaUsd(array $attrs = []): Cuenta
    {
        return Cuenta::factory()->create(array_merge([
            'moneda_id'  => $this->usd->id,
            'titular_id' => $this->titular->id,
            'tipo'       => 'plataforma',
            'activa'     => true,
        ], $attrs));
    }

    private function cuentaVes(array $attrs = []): Cuenta
    {
        return Cuenta::factory()->create(array_merge([
            'moneda_id'  => $this->ves->id,
            'titular_id' => $this->titular->id,
            'tipo'       => 'banco',
            'activa'     => true,
        ], $attrs));
    }

    private function payloadVenta(Cuenta $cUsd, Cuenta $cVes, float $tasa = 36.50, float $tasaMercado = 36.42, float $montoUsd = 100): array
    {
        return [
            'fecha'                 => '2026-05-11',
            'tipo_codigo'           => 'venta_usd',
            'operador_id'           => $this->operador->id,
            'tasa_aplicada'         => $tasa,
            'tasa_mercado_snapshot' => $tasaMercado,
            'fuente_tasa_mercado'   => 'bcv',
            'movimientos'           => [
                ['cuenta_id' => $cUsd->id, 'monto' => -$montoUsd, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => round($montoUsd * $tasa, 4), 'tasa_a_usd' => round(1 / $tasa, 8)],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Cambio que cuadra se registra correctamente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cambio_que_cuadra_se_registra_correctamente(): void
    {
        Queue::fake();
        TipoOperacion::factory()->cambio()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $operacion = $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'cambio',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => 3650.0, 'tasa_a_usd' => round(1 / 36.50, 8)],
            ],
        ]);

        $this->assertDatabaseHas('operaciones', ['id' => $operacion->id, 'estatus' => 'sin_verificar']);
        $this->assertCount(2, $operacion->movimientos);
        $this->assertEqualsWithDelta(-100.0, (float) $operacion->movimientos[0]->monto_usd_equivalente, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $operacion->movimientos[1]->monto_usd_equivalente, 0.001);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Cambio que no cuadra lanza ValidationException
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cambio_que_no_cuadra_lanza_validation_exception(): void
    {
        TipoOperacion::factory()->cambio()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $this->expectException(ValidationException::class);

        $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'cambio',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => 3500.0,  'tasa_a_usd' => round(1 / 36.50, 8)],
                // Diferencia ≈ 3500/36.50 - 100 = 95.89 - 100 = -4.11 USD → falla
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Descuadre dentro de tolerancia se acepta
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cambio_con_descuadre_dentro_de_tolerancia_se_acepta(): void
    {
        Queue::fake();
        TipoOperacion::factory()->cambio()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        // Diferencia = 0.005 USD (dentro del límite 0.01)
        $operacion = $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'cambio',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd->id, 'monto' => -100.0,   'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => 3649.8175, 'tasa_a_usd' => round(1 / 36.50, 8)],
                // 3649.8175 / 36.50 = 99.9950... → suma ≈ -0.005 USD
            ],
        ]);

        $this->assertDatabaseHas('operaciones', ['id' => $operacion->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Operación con cuenta inactiva falla
    // ─────────────────────────────────────────────────────────────────────────
    public function test_operacion_con_cuenta_inactiva_falla(): void
    {
        TipoOperacion::factory()->cambio()->create();
        $cUsdInactiva = $this->cuentaUsd(['activa' => false, 'alias' => 'TRUST-WALLET-INACTIVA']);
        $cVes         = $this->cuentaVes();

        try {
            $this->service->registrar([
                'fecha'       => '2026-05-11',
                'tipo_codigo' => 'cambio',
                'operador_id' => $this->operador->id,
                'movimientos' => [
                    ['cuenta_id' => $cUsdInactiva->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                    ['cuenta_id' => $cVes->id,          'monto' => 3650.0, 'tasa_a_usd' => round(1 / 36.50, 8)],
                ],
            ]);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('TRUST-WALLET-INACTIVA', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Gasto con un solo movimiento se registra
    // ─────────────────────────────────────────────────────────────────────────
    public function test_gasto_con_un_solo_movimiento_se_registra(): void
    {
        Queue::fake();
        TipoOperacion::factory()->gasto()->create();
        $cUsd = $this->cuentaUsd();

        $operacion = $this->service->registrar([
            'fecha'              => '2026-05-11',
            'tipo_codigo'        => 'gasto',
            'operador_id'        => $this->operador->id,
            'movimientos'        => [
                ['cuenta_id' => $cUsd->id, 'monto' => -25.0, 'tasa_a_usd' => 1.0],
            ],
        ]);

        $this->assertDatabaseHas('operaciones', ['id' => $operacion->id]);
        $this->assertCount(1, $operacion->movimientos);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Ajuste apertura con más de un movimiento falla
    // ─────────────────────────────────────────────────────────────────────────
    public function test_ajuste_apertura_con_mas_de_un_movimiento_falla(): void
    {
        TipoOperacion::factory()->ajusteApertura()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $this->expectException(ValidationException::class);

        $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'ajuste_apertura',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd->id, 'monto' => 500.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => 200.0, 'tasa_a_usd' => round(1 / 36.50, 8)],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Venta USD sin tasa_mercado_snapshot → ganancia directa cero
    // ─────────────────────────────────────────────────────────────────────────
    public function test_venta_usd_sin_tasa_mercado_snapshot_tiene_ganancia_directa_cero(): void
    {
        Queue::fake();
        TipoOperacion::factory()->ventaUsd()->create();
        $this->crearTasaDiariaUsdVes();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $payload = $this->payloadVenta($cUsd, $cVes);
        unset($payload['tasa_mercado_snapshot']);

        $operacion = $this->service->registrar($payload);

        $this->assertEquals('0.00', $operacion->ganancia_bruta_usd);
        $this->assertEquals('0.00', $operacion->ganancia_bruta_ves);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Operación dispatcha job de recálculo de saldos
    // ─────────────────────────────────────────────────────────────────────────
    public function test_operacion_dispatcha_job_de_recalculo_de_saldos(): void
    {
        Queue::fake();
        TipoOperacion::factory()->cambio()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'cambio',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => 3650.0,  'tasa_a_usd' => round(1 / 36.50, 8)],
            ],
        ]);

        Queue::assertPushed(RecalcularSaldoCuentaJob::class, function ($job) use ($cUsd, $cVes) {
            return in_array($cUsd->id, $job->cuentaIds) && in_array($cVes->id, $job->cuentaIds);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. Operación que afecta FIFO dispatcha ProcesarFifoOperacionJob
    // ─────────────────────────────────────────────────────────────────────────
    public function test_operacion_que_afecta_fifo_dispatcha_job_fifo(): void
    {
        Queue::fake();
        TipoOperacion::factory()->ventaUsd()->create();
        $this->crearTasaDiariaUsdVes();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $operacion = $this->service->registrar($this->payloadVenta($cUsd, $cVes));

        Queue::assertPushed(ProcesarFifoOperacionJob::class, function ($job) use ($operacion) {
            return $job->operacionId === $operacion->id;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. Unique origen_referencia previene duplicados a nivel de BD
    // ─────────────────────────────────────────────────────────────────────────
    public function test_unique_origen_referencia_previene_duplicados(): void
    {
        Queue::fake();
        TipoOperacion::factory()->ajusteApertura()->create();
        $cUsd = $this->cuentaUsd();

        $this->service->registrar([
            'fecha'              => '2026-05-11',
            'tipo_codigo'        => 'ajuste_apertura',
            'operador_id'        => $this->operador->id,
            'origen'             => 'importado',
            'origen_referencia'  => 'BOLIVARES!A5',
            'movimientos'        => [
                ['cuenta_id' => $cUsd->id, 'monto' => 500.0, 'tasa_a_usd' => 1.0],
            ],
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->service->registrar([
            'fecha'              => '2026-05-12',
            'tipo_codigo'        => 'ajuste_apertura',
            'operador_id'        => $this->operador->id,
            'origen'             => 'importado',
            'origen_referencia'  => 'BOLIVARES!A5',
            'movimientos'        => [
                ['cuenta_id' => $cUsd->id, 'monto' => 100.0, 'tasa_a_usd' => 1.0],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. moneda_id del movimiento viene de la cuenta, no del payload
    // ─────────────────────────────────────────────────────────────────────────
    public function test_movimiento_toma_moneda_de_la_cuenta_no_del_payload(): void
    {
        Queue::fake();
        TipoOperacion::factory()->gasto()->create();
        $cUsd = $this->cuentaUsd();

        $operacion = $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'gasto',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd->id, 'monto' => -10.0, 'tasa_a_usd' => 1.0],
            ],
        ]);

        $this->assertEquals($this->usd->id, $operacion->movimientos->first()->moneda_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 13. Compra USD calcula ganancia correctamente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_compra_usd_calcula_ganancia_correctamente(): void
    {
        Queue::fake();
        TipoOperacion::factory()->compraUsd()->create();
        $this->crearTasaDiariaUsdVes(36.20, 36.50);
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $tasaAplicada = 36.20;
        $tasaMercado  = 36.42;
        $montoUsd     = 100.0;

        // VES: tasa_a_usd = 1/tasa_aplicada para que cuadre (la casa pagó al tipo aplicado)
        $operacion = $this->service->registrar([
            'fecha'                 => '2026-05-11',
            'tipo_codigo'           => 'compra_usd',
            'operador_id'           => $this->operador->id,
            'tasa_aplicada'         => $tasaAplicada,
            'tasa_mercado_snapshot' => $tasaMercado,
            'movimientos'           => [
                ['cuenta_id' => $cUsd->id, 'monto' => $montoUsd, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => -round($montoUsd * $tasaAplicada, 4), 'tasa_a_usd' => round(1 / $tasaAplicada, 8)],
            ],
        ]);

        // ganancia_ves = 100 × (36.42 − 36.20) = 22.00 Bs
        $this->assertEqualsWithDelta(22.00, (float) $operacion->ganancia_bruta_ves, 0.01);
        // ganancia_usd = 22.00 / 36.42 = 0.6041 USD
        $this->assertEqualsWithDelta(0.60, (float) $operacion->ganancia_bruta_usd, 0.01);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 14. Comisión en VES registra ganancia_ves directa
    // ─────────────────────────────────────────────────────────────────────────
    public function test_comision_en_ves_registra_ganancia_ves_directa(): void
    {
        Queue::fake();
        TipoOperacion::factory()->comision()->create();
        $cVes = $this->cuentaVes();

        $operacion = $this->service->registrar([
            'fecha'                 => '2026-05-11',
            'tipo_codigo'           => 'comision',
            'operador_id'           => $this->operador->id,
            'tasa_mercado_snapshot' => 36.42,
            'movimientos'           => [
                ['cuenta_id' => $cVes->id, 'monto' => 500.0, 'tasa_a_usd' => round(1 / 36.42, 8)],
            ],
        ]);

        // La comisión se cobró en VES → ganancia_ves = 500 Bs (directo)
        $this->assertEqualsWithDelta(500.0, (float) $operacion->ganancia_bruta_ves, 0.01);
        // ganancia_usd = 500 / 36.42 ≈ 13.73 USD
        $this->assertEqualsWithDelta(13.73, (float) $operacion->ganancia_bruta_usd, 0.01);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 15. Traslado no genera ganancia
    // ─────────────────────────────────────────────────────────────────────────
    public function test_traslado_no_genera_ganancia(): void
    {
        Queue::fake();
        TipoOperacion::factory()->traslado()->create();
        $cUsd1 = $this->cuentaUsd();
        $cUsd2 = $this->cuentaUsd();

        $operacion = $this->service->registrar([
            'fecha'       => '2026-05-11',
            'tipo_codigo' => 'traslado',
            'operador_id' => $this->operador->id,
            'movimientos' => [
                ['cuenta_id' => $cUsd1->id, 'monto' => -500.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cUsd2->id, 'monto' =>  500.0, 'tasa_a_usd' => 1.0],
            ],
        ]);

        $this->assertEquals('0.00', $operacion->ganancia_bruta_usd);
        $this->assertEquals('0.00', $operacion->ganancia_bruta_ves);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 17. Compra USD genera ganancia (genera_ganancia = true)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_compra_usd_genera_ganancia(): void
    {
        Queue::fake();
        TipoOperacion::factory()->compraUsd()->create(['genera_ganancia' => true]);
        $this->crearTasaDiariaUsdVes(36.20, 36.50);
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $operacion = $this->service->registrar([
            'fecha'                 => '2026-05-11',
            'tipo_codigo'           => 'compra_usd',
            'operador_id'           => $this->operador->id,
            'tasa_aplicada'         => 36.20,
            'tasa_mercado_snapshot' => 36.42,
            'movimientos'           => [
                ['cuenta_id' => $cUsd->id, 'monto' => 100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' => -3620.0, 'tasa_a_usd' => round(1 / 36.20, 8)],
            ],
        ]);

        $this->assertNotEquals('0.00', $operacion->ganancia_bruta_usd);
        $this->assertNotEquals('0.00', $operacion->ganancia_bruta_ves);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 18. Snapshot al cierre: tasa_mercado se actualiza al cerrar
    // ─────────────────────────────────────────────────────────────────────────
    public function test_snapshot_al_cierre_actualiza_tasa_mercado(): void
    {
        Queue::fake();
        TipoOperacion::factory()->ventaUsd()->create();
        $this->crearTasaDiariaUsdVes();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $operacion = $this->service->registrar($this->payloadVenta($cUsd, $cVes, 36.50, 36.42, 100));

        // Verificar que tiene la tasa original
        $this->assertEquals(36.42, (float) $operacion->tasa_mercado_snapshot);

        // Simular cierre con nueva tasa de mercado
        $cuentaOrigen = Cuenta::factory()->create(['moneda_id' => $this->usd->id, 'titular_id' => $this->titular->id]);
        $cuentaDestino = Cuenta::factory()->create(['moneda_id' => $this->ves->id, 'titular_id' => $this->titular->id]);

        // Crear transacción confirmada
        $operacion->update(['estado' => 'en_progreso']);
        \App\Models\Transaccion::factory()->create([
            'operacion_id'     => $operacion->id,
            'cuenta_origen_id' => $cuentaOrigen->id,
            'cuenta_destino_id' => $cuentaDestino->id,
            'moneda_id'        => $this->usd->id,
            'monto'            => 100.0,
            'estado'           => 'confirmada',
            'metodo_pago'      => 'efectivo',
            'orden'            => 1,
        ]);

        // Cerrar con nueva tasa
        $operacionCerrada = $this->service->cerrarOperacion(
            $operacion,
            $this->operador,
            36.55,
            'bcv'
        );

        // La tasa de mercado debe haberse actualizado
        $this->assertEquals(36.55, (float) $operacionCerrada->tasa_mercado_snapshot);
        $this->assertEquals('bcv', $operacionCerrada->fuente_tasa_mercado);
    }

    private function payloadSolicitudVenta(float $tasa = 36.50, float $monto = 100): array
    {
        return [
            'fecha'            => '2026-07-22',
            'tipo_codigo'      => 'venta_usd',
            'moneda_codigo'    => 'USD',
            'operador_id'      => $this->operador->id,
            'tasa_aplicada'    => $tasa,
            'monto_solicitado' => $monto,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // FLUJO MULTI-PASO: crearSolicitud
    // ════════════════════════════════════════════════════════════════

    // ─────────────────────────────────────────────────────────────────────────
    // 20. crearSolicitud sin transacciones crea operación en solicitud
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_sin_transacciones_crea_operacion_en_solicitud(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();

        $operacion = $this->service->crearSolicitud(
            $this->payloadSolicitudVenta()
        );

        $this->assertDatabaseHas('operaciones', [
            'id'    => $operacion->id,
            'estado' => 'solicitud',
        ]);
        $this->assertEquals('solicitud', $operacion->estado);
        $this->assertNull($operacion->movimientos->first());
        $this->assertCount(0, $operacion->transacciones);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 21. crearSolicitud con transacciones crea transacciones pendientes
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_con_transacciones_crea_transacciones_pendientes(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $payload = $this->payloadSolicitudVenta(36.50, 100);
        $payload['transacciones'] = [
            [
                'cuenta_origen_id'  => $cUsd->id,
                'cuenta_destino_id' => $cVes->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
                'metodo_pago'       => 'transferencia',
            ],
        ];

        $operacion = $this->service->crearSolicitud($payload);

        $this->assertEquals('solicitud', $operacion->estado);
        $this->assertCount(1, $operacion->transacciones);

        $transaccion = $operacion->transacciones->first();
        $this->assertEquals('pendiente', $transaccion->estado);
        $this->assertEquals($cUsd->id, $transaccion->cuenta_origen_id);
        $this->assertEquals($cVes->id, $transaccion->cuenta_destino_id);
        $this->assertEquals(100.0, (float) $transaccion->monto);
        $this->assertEquals(36.50, (float) $transaccion->tasa_aplicada);
        $this->assertEquals('transferencia', $transaccion->metodo_pago);
        $this->assertEquals(1, $transaccion->orden);

        $this->assertDatabaseHas('transacciones', [
            'id'                => $transaccion->id,
            'operacion_id'      => $operacion->id,
            'estado'            => 'pendiente',
            'cuenta_origen_id'  => $cUsd->id,
            'cuenta_destino_id' => $cVes->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 22. crearSolicitud con cuenta origen inactiva lanza ValidationException
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_con_cuenta_inactiva_lanza_error(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();
        $cInactiva = $this->cuentaUsd(['activa' => false]);
        $cVes = $this->cuentaVes();

        $payload = $this->payloadSolicitudVenta();
        $payload['transacciones'] = [
            [
                'cuenta_origen_id'  => $cInactiva->id,
                'cuenta_destino_id' => $cVes->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cuentas están inactivas');

        $this->service->crearSolicitud($payload);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 23. crearSolicitud con cuenta destino inactiva lanza ValidationException
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_con_cuenta_destino_inactiva_lanza_error(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();
        $cUsd = $this->cuentaUsd();
        $cInactiva = $this->cuentaVes(['activa' => false]);

        $payload = $this->payloadSolicitudVenta();
        $payload['transacciones'] = [
            [
                'cuenta_origen_id'  => $cUsd->id,
                'cuenta_destino_id' => $cInactiva->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cuentas están inactivas');

        $this->service->crearSolicitud($payload);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 24. crearSolicitud con transacciones usa tasa_aplicada por transacción
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_con_transaccion_usa_tasa_propia(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $payload = $this->payloadSolicitudVenta(36.50, 100);
        $payload['transacciones'] = [
            [
                'cuenta_origen_id'  => $cUsd->id,
                'cuenta_destino_id' => $cVes->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
                'tasa_aplicada'     => 37.00,
            ],
        ];

        $operacion = $this->service->crearSolicitud($payload);

        $transaccion = $operacion->transacciones->first();
        $this->assertEquals(37.00, (float) $transaccion->tasa_aplicada);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 25. crearSolicitud con múltiples transacciones asigna orden correcto
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_con_multiples_transacciones_asigna_orden(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();
        $cUsd2 = $this->cuentaUsd();

        $payload = $this->payloadSolicitudVenta(36.50, 200);
        $payload['transacciones'] = [
            [
                'cuenta_origen_id'  => $cUsd->id,
                'cuenta_destino_id' => $cVes->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
            ],
            [
                'cuenta_origen_id'  => $cUsd2->id,
                'cuenta_destino_id' => $cVes->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
            ],
        ];

        $operacion = $this->service->crearSolicitud($payload);

        $this->assertCount(2, $operacion->transacciones);
        $this->assertEquals(1, $operacion->transacciones[0]->orden);
        $this->assertEquals(2, $operacion->transacciones[1]->orden);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 26. crearSolicitud con transacciones permite gestionarlas luego
    // ─────────────────────────────────────────────────────────────────────────
    public function test_crear_solicitud_con_transacciones_pendientes_son_gestionables(): void
    {
        TipoOperacion::factory()->ventaUsd()->create();
        $cUsd = $this->cuentaUsd();
        $cVes = $this->cuentaVes();

        $payload = $this->payloadSolicitudVenta(36.50, 100);
        $payload['transacciones'] = [
            [
                'cuenta_origen_id'  => $cUsd->id,
                'cuenta_destino_id' => $cVes->id,
                'moneda_id'         => $this->usd->id,
                'monto'             => 100.0,
            ],
        ];

        $operacion = $this->service->crearSolicitud($payload);

        // Pasar a en_progreso
        $operacion = $this->service->iniciarOperacion($operacion);
        $this->assertEquals('en_progreso', $operacion->estado);

        // Confirmar la transacción
        $transaccion = $operacion->fresh()->transacciones->first();
        $transaccion->update([
            'estado'           => 'confirmada',
            'confirmada_en'    => now(),
            'confirmada_por_id' => $this->operador->id,
        ]);

        $this->assertEquals('confirmada', $transaccion->fresh()->estado);
    }
}
