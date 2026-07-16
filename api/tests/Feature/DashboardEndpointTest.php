<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TasaDiaria;
use App\Models\TasaMercado;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);
        Cache::flush();

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    private function moneda(string $codigo): Moneda
    {
        return Moneda::where('codigo', $codigo)->first();
    }

    // ── general ───────────────────────────────────────────────────────

    public function test_general_returns_all_sections(): void
    {
        $usd = $this->moneda('USD');
        $ves = $this->moneda('VES');

        TasaDiaria::factory()->create([
            'moneda_base_id'     => $usd->id,
            'moneda_cotizada_id' => $ves->id,
            'tasa_compra'        => 36.00,
            'tasa_venta'         => 36.50,
            'vigente_hasta'      => null,
        ]);

        TasaMercado::factory()->create([
            'fuente'         => 'bcv',
            'moneda_base_id' => $usd->id,
            'moneda_cotizada_id' => $ves->id,
            'valor'          => 36.20,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/general');

        $response->assertOk()
            ->assertJsonStructure([
                'tasas_vigentes',
                'referencia_mercado' => ['bcv', 'binance_p2p_buy', 'binance_p2p_sell', 'spread_binance', 'spread_vigente_vs_bcv_pct'],
                'alertas' => ['operaciones_sin_tasa_referencia_hoy', 'pares_sin_tasa_vigente'],
            ])
            ->assertJsonPath('tasas_vigentes.0.par', 'USD/VES')
            ->assertJsonPath('tasas_vigentes.0.tasa_venta', 36.50)
            ->assertJsonPath('referencia_mercado.bcv.valor', 36.20);
    }

    public function test_general_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/general')->assertUnauthorized();
    }

    public function test_general_accessible_by_any_authenticated_user(): void
    {
        $this->actingAs($this->operador)
            ->getJson('/api/v1/dashboard/general')
            ->assertOk();
    }

    public function test_general_alertas_ops_sin_tasa(): void
    {
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();

        Operacion::factory()->create([
            'fecha'               => today(),
            'tipo_operacion_id'   => $tipo->id,
            'operador_id'         => $this->operador->id,
            'sin_tasa_referencia' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/general');

        $response->assertOk()
            ->assertJsonPath('alertas.operaciones_sin_tasa_referencia_hoy', 1);
    }

    public function test_general_pares_sin_tasa(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/general');

        $response->assertOk()
            ->assertJsonPath('alertas.pares_sin_tasa_vigente', ['USD/VES', 'USDT/VES']);
    }

    public function test_general_mercado_null_sin_datos(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/general');

        $response->assertOk()
            ->assertJsonPath('referencia_mercado.bcv', null);
    }

    // ── tasas-referencia ──────────────────────────────────────────────

    public function test_tasas_referencia_returns_sources(): void
    {
        $usd = $this->moneda('USD');
        $ves = $this->moneda('VES');

        TasaMercado::factory()->create([
            'fuente'           => 'bcv',
            'moneda_base_id'   => $usd->id,
            'moneda_cotizada_id' => $ves->id,
            'valor'            => 36.50,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/tasas-referencia');

        $response->assertOk()
            ->assertJsonStructure(['bcv', 'binance_p2p'])
            ->assertJsonPath('bcv.tasa', 36.50);
    }

    public function test_tasas_referencia_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/tasas-referencia')
            ->assertUnauthorized();
    }

    public function test_tasas_referencia_null_sin_datos(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/tasas-referencia');

        $response->assertOk()
            ->assertJsonPath('bcv', null)
            ->assertJsonPath('binance_p2p', null);
    }

    // ── resumen ───────────────────────────────────────────────────────

    private function crearOperacionBase(
        TipoOperacion $tipo,
        User $operador,
        \Carbon\Carbon $fecha,
    ): Operacion {
        $usd = $this->moneda('USD');
        $ves = $this->moneda('VES');
        $titular = Titular::factory()->create();

        $cUsd = Cuenta::factory()->create([
            'moneda_id'  => $usd->id,
            'titular_id' => $titular->id,
            'tipo'       => 'plataforma',
        ]);
        $cVes = Cuenta::factory()->create([
            'moneda_id'  => $ves->id,
            'titular_id' => $titular->id,
            'tipo'       => 'banco',
        ]);

        $op = Operacion::factory()->create([
            'fecha'              => $fecha,
            'tipo_operacion_id'  => $tipo->id,
            'operador_id'        => $operador->id,
            'ganancia_bruta_usd' => 10,
            'ganancia_neta_usd'  => 8,
        ]);

        $tasaVes = round(1 / 36.50, 8);
        $op->movimientos()->createMany([
            ['cuenta_id' => $cUsd->id, 'moneda_id' => $usd->id, 'monto' => 100, 'tasa_a_usd' => 1, 'monto_usd_equivalente' => 100, 'orden' => 1],
            ['cuenta_id' => $cVes->id, 'moneda_id' => $ves->id, 'monto' => -3650, 'tasa_a_usd' => $tasaVes, 'monto_usd_equivalente' => 100, 'orden' => 2],
        ]);

        return $op;
    }

    public function test_resumen_default_hoy(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();
        $this->crearOperacionBase($tipo, $this->operador, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen');

        $response->assertOk()
            ->assertJsonPath('periodo.desde', today()->toDateString())
            ->assertJsonPath('periodo.hasta', today()->toDateString())
            ->assertJsonPath('operaciones.total', 1)
            ->assertJsonPath('operaciones.compras', 1);
    }

    public function test_resumen_ganancias(): void
    {
        $tipoCompra = TipoOperacion::where('codigo', 'compra_usd')->first();
        $tipoVenta  = TipoOperacion::where('codigo', 'venta_usd')->first();

        $this->crearOperacionBase($tipoCompra, $this->operador, today());
        $this->crearOperacionBase($tipoVenta, $this->operador, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen');

        $response->assertOk()
            ->assertJsonPath('operaciones.total', 2)
            ->assertJsonPath('operaciones.compras', 1)
            ->assertJsonPath('operaciones.ventas', 1)
            ->assertJsonPath('ganancias.bruta_usd', 20)
            ->assertJsonPath('ganancias.neta_usd', 16);
    }

    public function test_resumen_filtra_por_fecha(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();

        $this->crearOperacionBase($tipo, $this->operador, now()->subDays(2));

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen?' . http_build_query([
                'fecha_desde' => today()->toDateString(),
                'fecha_hasta' => today()->toDateString(),
            ]));

        $response->assertOk()
            ->assertJsonPath('operaciones.total', 0);
    }

    public function test_resumen_filtra_por_operador(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();
        $otro = User::factory()->create(['activo' => true]);

        $this->crearOperacionBase($tipo, $this->operador, today());
        $this->crearOperacionBase($tipo, $otro, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen?operador_id=' . $this->operador->id);

        $response->assertOk()
            ->assertJsonPath('operaciones.total', 1);
    }

    public function test_resumen_filtra_por_moneda(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();
        $this->crearOperacionBase($tipo, $this->operador, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen?moneda=USD');

        $response->assertOk()
            ->assertJsonPath('operaciones.total', 1);
    }

    public function test_resumen_moneda_sin_coincidencias(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();
        $this->crearOperacionBase($tipo, $this->operador, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen?moneda=COP');

        $response->assertOk()
            ->assertJsonPath('operaciones.total', 0);
    }

    public function test_resumen_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/resumen')->assertUnauthorized();
    }

    public function test_resumen_invalid_operador_id_returns_422(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen?operador_id=99999')
            ->assertStatus(422);
    }

    public function test_resumen_returns_volumenes_por_moneda(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();
        $this->crearOperacionBase($tipo, $this->operador, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen');

        $response->assertOk()
            ->assertJsonStructure(['volumenes']);
    }

    public function test_resumen_returns_por_operador(): void
    {
        $tipo = TipoOperacion::where('codigo', 'compra_usd')->first();
        $this->crearOperacionBase($tipo, $this->operador, today());

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/resumen');

        $response->assertOk()
            ->assertJsonCount(1, 'por_operador')
            ->assertJsonPath('por_operador.0.operador', $this->operador->name);
    }
}
