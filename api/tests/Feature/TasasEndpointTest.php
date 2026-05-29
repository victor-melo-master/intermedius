<?php

namespace Tests\Feature;

use App\Models\Moneda;
use App\Models\TasaMercado;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TasasEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Moneda $usd;
    private Moneda $ves;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->user = User::factory()->create(['activo' => true]);
        $this->user->assignRole('operador');

        $this->usd = Moneda::where('codigo', 'USD')->first();
        $this->ves = Moneda::where('codigo', 'VES')->first();
    }

    private function crearTasaEnBD(string $fuente, float $valor, string $par = 'USD/VES'): TasaMercado
    {
        $codigos = explode('/', $par);
        $base    = Moneda::where('codigo', $codigos[0])->firstOrCreate(
            ['codigo' => $codigos[0]],
            ['nombre' => $codigos[0], 'es_fiat' => true, 'es_cripto' => false, 'decimales' => 2, 'activa' => true]
        );
        $cotizada = Moneda::where('codigo', $codigos[1])->firstOrCreate(
            ['codigo' => $codigos[1]],
            ['nombre' => $codigos[1], 'es_fiat' => true, 'es_cripto' => false, 'decimales' => 2, 'activa' => true]
        );

        return TasaMercado::create([
            'fuente'             => $fuente,
            'moneda_base_id'     => $base->id,
            'moneda_cotizada_id' => $cotizada->id,
            'valor'              => $valor,
            'capturado_en'       => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. actuales retorna tasas del cache
    // ─────────────────────────────────────────────────────────────────────────
    public function test_actuales_retorna_tasas_del_cache(): void
    {
        Cache::put('tasa_actual:bcv', ['fuente' => 'bcv', 'valor' => 36.42, 'par' => 'USD/VES', 'capturado_en' => now()->toIso8601String()], 1800);
        Cache::put('tasa_actual:binance_p2p_sell', ['fuente' => 'binance_p2p_sell', 'valor' => 36.70, 'par' => 'USDT/VES', 'capturado_en' => now()->toIso8601String()], 1800);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasas/actuales');

        $response->assertOk()
            ->assertJsonStructure(['tasas', 'spreads'])
            ->assertJsonPath('tasas.bcv.valor', 36.42);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. actuales cae de vuelta a BD si no hay cache
    // ─────────────────────────────────────────────────────────────────────────
    public function test_actuales_usa_bd_como_fallback(): void
    {
        Cache::flush();
        $this->crearTasaEnBD('bcv', 36.50);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasas/actuales');

        $response->assertOk()
            ->assertJsonPath('tasas.bcv.valor', 36.50);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. spreads se calculan correctamente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_spreads_se_calculan_correctamente(): void
    {
        Cache::put('tasa_actual:bcv',              ['fuente' => 'bcv',              'valor' => 36.00, 'capturado_en' => now()->toIso8601String()], 1800);
        Cache::put('tasa_actual:binance_p2p_sell', ['fuente' => 'binance_p2p_sell', 'valor' => 37.80, 'capturado_en' => now()->toIso8601String()], 1800);
        Cache::put('tasa_actual:binance_p2p_buy',  ['fuente' => 'binance_p2p_buy',  'valor' => 37.60, 'capturado_en' => now()->toIso8601String()], 1800);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasas/actuales');

        $response->assertOk();
        $spreads = $response->json('spreads');

        // usdt_sell_vs_bcv = (37.80 - 36.00) / 36.00 * 100 = 5.0
        $this->assertEqualsWithDelta(5.0, $spreads['usdt_sell_vs_bcv'], 0.01);
        // usdt_buy_vs_bcv = (37.60 - 36.00) / 36.00 * 100 ≈ 4.4444
        $this->assertEqualsWithDelta(4.4444, $spreads['usdt_buy_vs_bcv'], 0.01);
        // usdt_sell_vs_buy = (37.80 - 37.60) / 37.60 * 100 ≈ 0.5319
        $this->assertEqualsWithDelta(0.5319, $spreads['usdt_sell_vs_buy'], 0.01);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. spreads son null si faltan datos
    // ─────────────────────────────────────────────────────────────────────────
    public function test_spreads_son_null_sin_datos_suficientes(): void
    {
        Cache::flush();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasas/actuales');

        $response->assertOk();
        $spreads = $response->json('spreads');

        $this->assertNull($spreads['usdt_sell_vs_bcv']);
        $this->assertNull($spreads['usdt_buy_vs_bcv']);
        $this->assertNull($spreads['usdt_sell_vs_buy']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. historico retorna registros paginados
    // ─────────────────────────────────────────────────────────────────────────
    public function test_historico_retorna_registros_paginados(): void
    {
        $this->crearTasaEnBD('bcv', 36.42);
        $this->crearTasaEnBD('bcv', 36.45);
        $this->crearTasaEnBD('paralelo', 36.80);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasas/historico');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'current_page']);

        $this->assertCount(3, $response->json('data'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. historico filtra por fuente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_historico_filtra_por_fuente(): void
    {
        $this->crearTasaEnBD('bcv', 36.42);
        $this->crearTasaEnBD('bcv', 36.45);
        $this->crearTasaEnBD('paralelo', 36.80);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasas/historico?fuente=bcv');

        $response->assertOk();
        $this->assertEquals(2, $response->json('total'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. actuales falla sin autenticación
    // ─────────────────────────────────────────────────────────────────────────
    public function test_actuales_falla_sin_autenticacion(): void
    {
        $this->getJson('/api/v1/tasas/actuales')->assertUnauthorized();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. SincronizarTasasJob persiste en BD y cache
    // ─────────────────────────────────────────────────────────────────────────
    public function test_sincronizar_tasas_job_persiste_en_bd_y_cache(): void
    {
        Http::fake([
            've.dolarapi.com/v1/dolares/oficial'   => Http::response(['promedio' => 36.42], 200),
            've.dolarapi.com/v1/dolares/paralelo'  => Http::response(['promedio' => 36.80], 200),
            'p2p.binance.com/*'                    => Http::response([
                'code' => '000000',
                'data' => [
                    ['adv' => ['price' => '36.50']],
                    ['adv' => ['price' => '36.60']],
                ],
                'total' => 2,
            ], 200),
        ]);

        $job = new \App\Jobs\SincronizarTasasJob();
        $job->handle(new \App\Services\Tasas\TasasMercadoService());

        // BCV y paralelo guardados en BD
        $this->assertDatabaseHas('tasas_mercado', ['fuente' => 'bcv',      'valor' => 36.42]);
        $this->assertDatabaseHas('tasas_mercado', ['fuente' => 'paralelo', 'valor' => 36.80]);

        // BCV y paralelo en cache
        $this->assertNotNull(Cache::get('tasa_actual:bcv'));
        $this->assertNotNull(Cache::get('tasa_actual:paralelo'));
        $this->assertEquals(36.42, Cache::get('tasa_actual:bcv')['valor']);
    }
}
