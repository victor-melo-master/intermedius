<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Services\Tasas\TasasMercadoService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

#[IgnoreDeprecations]
class TasasMercadoServiceTest extends TestCase
{
    private TasasMercadoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TasasMercadoService();
    }

    private function fakeBcvResponse(float $promedio = 36.42): array
    {
        return [
            'nombre'            => 'Oficial',
            'fuente'            => 'VED',
            'promedio'          => $promedio,
            'promedioCompra'    => $promedio - 0.05,
            'promedioVenta'     => $promedio + 0.05,
            'fechaActualizacion' => now()->toIso8601String(),
        ];
    }

    private function fakeBinanceResponse(array $precios): array
    {
        return [
            'code' => '000000',
            'data' => collect($precios)->map(fn ($p) => [
                'adv'        => ['price' => (string) $p, 'asset' => 'USDT', 'fiatUnit' => 'VES'],
                'advertiser' => ['nickName' => 'Test'],
            ])->all(),
            'total' => count($precios),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. obtenerBcv parsea correctamente el promedio
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_bcv_parsea_promedio(): void
    {
        Http::fake([
            've.dolarapi.com/v1/dolares/oficial' => Http::response($this->fakeBcvResponse(36.42), 200),
        ]);

        $resultado = $this->service->obtenerBcv();

        $this->assertNotNull($resultado);
        $this->assertEquals('bcv', $resultado['fuente']);
        $this->assertEquals('USD/VES', $resultado['par']);
        $this->assertEqualsWithDelta(36.42, $resultado['valor'], 0.001);
        $this->assertIsArray($resultado['payload']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. obtenerBcv retorna null si la API falla
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_bcv_retorna_null_si_api_falla(): void
    {
        Http::fake([
            've.dolarapi.com/v1/dolares/oficial' => Http::response([], 500),
        ]);

        Log::shouldReceive('warning')->once();

        $resultado = $this->service->obtenerBcv();

        $this->assertNull($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. obtenerParalelo parsea correctamente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_paralelo_parsea_correctamente(): void
    {
        Http::fake([
            've.dolarapi.com/v1/dolares/paralelo' => Http::response($this->fakeBcvResponse(36.80), 200),
        ]);

        $resultado = $this->service->obtenerParalelo();

        $this->assertNotNull($resultado);
        $this->assertEquals('paralelo', $resultado['fuente']);
        $this->assertEqualsWithDelta(36.80, $resultado['valor'], 0.001);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. obtenerBinanceP2P calcula promedio, mediana, min, max correctamente
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_binance_p2p_calcula_estadisticas(): void
    {
        $precios = [36.50, 36.40, 36.60, 36.45, 36.55];

        Http::fake([
            'p2p.binance.com/*' => Http::response($this->fakeBinanceResponse($precios), 200),
        ]);

        $resultado = $this->service->obtenerBinanceP2P('BUY');

        $this->assertNotNull($resultado);
        $this->assertEquals('binance_p2p_buy', $resultado['fuente']);
        $this->assertEquals('USDT/VES', $resultado['par']);
        $this->assertEqualsWithDelta(36.50, $resultado['valor'], 0.001);    // promedio de [36.40,36.45,36.50,36.55,36.60]
        $this->assertEqualsWithDelta(36.50, $resultado['mediana'], 0.001);  // mediana
        $this->assertEqualsWithDelta(36.40, $resultado['min'], 0.001);
        $this->assertEqualsWithDelta(36.60, $resultado['max'], 0.001);
        $this->assertEquals(5, $resultado['muestras']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. obtenerBinanceP2P SELL usa fuente correcta
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_binance_p2p_sell_usa_fuente_correcta(): void
    {
        Http::fake([
            'p2p.binance.com/*' => Http::response($this->fakeBinanceResponse([36.70, 36.80]), 200),
        ]);

        $resultado = $this->service->obtenerBinanceP2P('SELL');

        $this->assertNotNull($resultado);
        $this->assertEquals('binance_p2p_sell', $resultado['fuente']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. obtenerBinanceP2P retorna null si respuesta vacía
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_binance_p2p_retorna_null_si_data_vacia(): void
    {
        Http::fake([
            'p2p.binance.com/*' => Http::response(['code' => '000000', 'data' => [], 'total' => 0], 200),
        ]);

        $resultado = $this->service->obtenerBinanceP2P('BUY');

        $this->assertNull($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. obtenerBinanceP2P retorna null si la API falla
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_binance_p2p_retorna_null_si_api_falla(): void
    {
        Http::fake([
            'p2p.binance.com/*' => Http::response([], 503),
        ]);

        Log::shouldReceive('warning')->once();

        $resultado = $this->service->obtenerBinanceP2P('BUY');

        $this->assertNull($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Mediana se calcula correctamente para lista par
    // ─────────────────────────────────────────────────────────────────────────
    public function test_mediana_con_lista_par(): void
    {
        // 4 elementos: sorted [36.40, 36.50, 36.60, 36.70] → mediana = (36.50+36.60)/2 = 36.55
        Http::fake([
            'p2p.binance.com/*' => Http::response($this->fakeBinanceResponse([36.70, 36.40, 36.60, 36.50]), 200),
        ]);

        $resultado = $this->service->obtenerBinanceP2P('BUY');

        $this->assertEqualsWithDelta(36.55, $resultado['mediana'], 0.001);
    }
}
