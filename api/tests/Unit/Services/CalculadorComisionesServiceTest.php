<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\ComisionCuenta;
use App\Models\ComisionOperacion;
use App\Models\ComisionOperador;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TasaDiaria;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use App\Services\Configuracion\CalculadorComisionesService;
use App\Services\Operaciones\RegistroOperacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

#[IgnoreDeprecations]
class CalculadorComisionesServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalculadorComisionesService $service;
    private RegistroOperacionService $registro;
    private Moneda $usd;
    private Moneda $ves;
    private Titular $titular;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->service  = $this->app->make(CalculadorComisionesService::class);
        $this->registro = $this->app->make(RegistroOperacionService::class);

        $this->usd      = Moneda::factory()->usd()->create();
        $this->ves      = Moneda::factory()->ves()->create();
        $this->titular  = Titular::factory()->create();
        $this->operador = User::factory()->create(['titular_id' => $this->titular->id]);
    }

    private function crearTasaDiariaUsdVes(): TasaDiaria
    {
        return TasaDiaria::create([
            'fecha'              => now()->toDateString(),
            'moneda_base_id'     => $this->usd->id,
            'moneda_cotizada_id' => $this->ves->id,
            'tasa_compra'        => 36.20,
            'tasa_venta'         => 36.50,
            'definida_por_id'    => $this->operador->id,
            'notas'              => null,
            'vigente_desde'      => now()->subMinute(),
            'vigente_hasta'      => null,
        ]);
    }

    private function registrarVenta(): Operacion
    {
        $this->crearTasaDiariaUsdVes();
        TipoOperacion::factory()->ventaUsd()->create();

        $cUsd = Cuenta::factory()->create(['moneda_id' => $this->usd->id, 'titular_id' => $this->titular->id, 'activa' => true]);
        $cVes = Cuenta::factory()->create(['moneda_id' => $this->ves->id, 'titular_id' => $this->titular->id, 'activa' => true]);

        return $this->registro->registrar([
            'fecha'                 => now()->toDateString(),
            'tipo_codigo'           => 'venta_usd',
            'operador_id'           => $this->operador->id,
            'tasa_aplicada'         => 36.50,
            'tasa_mercado_snapshot' => 36.42,
            'movimientos'           => [
                ['cuenta_id' => $cUsd->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' =>  3650.0, 'tasa_a_usd' => round(1 / 36.50, 8)],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Sin comisiones configuradas, totales son 0 y netas = brutas
    // ─────────────────────────────────────────────────────────────────────────
    public function test_sin_comisiones_totales_son_cero_netas_iguales_brutas(): void
    {
        $op = $this->registrarVenta();

        $op->refresh();
        $this->assertEquals('0.0000', $op->total_comisiones_usd);
        $this->assertEqualsWithDelta((float) $op->ganancia_bruta_usd, (float) $op->ganancia_neta_usd, 0.0001);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. ComisionCuenta porcentaje se aplica y reduce ganancia neta
    // ─────────────────────────────────────────────────────────────────────────
    public function test_comision_cuenta_porcentaje_reduce_ganancia_neta(): void
    {
        $cUsd = Cuenta::factory()->create(['moneda_id' => $this->usd->id, 'titular_id' => $this->titular->id, 'activa' => true]);
        $cVes = Cuenta::factory()->create(['moneda_id' => $this->ves->id, 'titular_id' => $this->titular->id, 'activa' => true]);

        // 1% sobre egreso de la cuenta USD
        ComisionCuenta::create([
            'cuenta_id'     => $cUsd->id,
            'descripcion'   => 'Fee USD egreso 1%',
            'tipo_calculo'  => 'porcentaje',
            'valor'         => 1.0,
            'moneda_id'     => $this->usd->id,
            'aplica_a'      => 'egreso',
            'vigente_desde' => now()->subDay()->toDateString(),
            'activa'        => true,
        ]);

        $this->crearTasaDiariaUsdVes();
        TipoOperacion::factory()->ventaUsd()->create();

        $op = $this->registro->registrar([
            'fecha'                 => now()->toDateString(),
            'tipo_codigo'           => 'venta_usd',
            'operador_id'           => $this->operador->id,
            'tasa_aplicada'         => 36.50,
            'tasa_mercado_snapshot' => 36.42,
            'movimientos'           => [
                ['cuenta_id' => $cUsd->id, 'monto' => -100.0, 'tasa_a_usd' => 1.0],
                ['cuenta_id' => $cVes->id, 'monto' =>  3650.0, 'tasa_a_usd' => round(1 / 36.50, 8)],
            ],
        ]);

        $op->refresh();

        // 1% de 100 USD = 1 USD comisión
        $this->assertEqualsWithDelta(1.0, (float) $op->total_comisiones_usd, 0.01);
        // ganancia neta < ganancia bruta
        $this->assertLessThan((float) $op->ganancia_bruta_usd, (float) $op->ganancia_neta_usd);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. ComisionOperador monto_fijo persiste en comisiones_operacion
    // ─────────────────────────────────────────────────────────────────────────
    public function test_comision_operador_monto_fijo_persiste(): void
    {
        ComisionOperador::create([
            'titular_id'    => $this->titular->id,
            'descripcion'   => 'Pago fijo operador $10',
            'tipo_calculo'  => 'monto_fijo',
            'valor'         => 10.0,
            'moneda_id'     => $this->usd->id,
            'base_calculo'  => 'monto_operacion',
            'vigente_desde' => now()->subDay()->toDateString(),
            'activa'        => true,
        ]);

        $op = $this->registrarVenta();
        $op->refresh();

        $comisiones = ComisionOperacion::where('operacion_id', $op->id)
            ->where('tipo', 'operador')
            ->get();

        $this->assertCount(1, $comisiones);
        $this->assertEqualsWithDelta(10.0, (float) $comisiones->first()->monto, 0.01);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. aplicarAOperacion es idempotente (segunda llamada no duplica)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_aplicar_a_operacion_es_idempotente(): void
    {
        $op = $this->registrarVenta();

        $this->service->aplicarAOperacion($op->fresh(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']));

        $count1 = ComisionOperacion::where('operacion_id', $op->id)->count();

        $this->service->aplicarAOperacion($op->fresh(['movimientos.cuenta.banco', 'movimientos.moneda', 'operador.titular', 'tipoOperacion']));

        $count2 = ComisionOperacion::where('operacion_id', $op->id)->count();

        $this->assertEquals($count1, $count2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. editarComision actualiza razon_edicion y recalcula
    // ─────────────────────────────────────────────────────────────────────────
    public function test_editar_comision_actualiza_razon_y_recalcula(): void
    {
        ComisionOperador::create([
            'titular_id'    => $this->titular->id,
            'descripcion'   => 'Pago fijo $5',
            'tipo_calculo'  => 'monto_fijo',
            'valor'         => 5.0,
            'moneda_id'     => $this->usd->id,
            'base_calculo'  => 'monto_operacion',
            'vigente_desde' => now()->subDay()->toDateString(),
            'activa'        => true,
        ]);

        $op       = $this->registrarVenta();
        $admin    = User::factory()->create();
        $comision = ComisionOperacion::where('operacion_id', $op->id)->where('tipo', 'operador')->first();

        $this->service->editarComision(
            $comision,
            ['monto' => 8.0, 'monto_usd_equivalente' => 8.0],
            $admin,
            'Ajuste manual aprobado por gerencia'
        );

        $comision->refresh();
        $op->refresh();

        $this->assertEquals('8.0000', $comision->monto);
        $this->assertEquals($admin->id, $comision->editada_por_id);
        $this->assertNotNull($comision->editada_at);
        $this->assertEqualsWithDelta(8.0, (float) $op->total_comisiones_usd, 0.01);
    }
}
