<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Models\User;
use App\Services\Configuracion\TasaDiariaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

#[IgnoreDeprecations]
class TasaDiariaServiceTest extends TestCase
{
    use RefreshDatabase;

    private TasaDiariaService $service;
    private Moneda $usd;
    private Moneda $ves;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(TasaDiariaService::class);
        $this->usd     = Moneda::factory()->usd()->create();
        $this->ves     = Moneda::factory()->ves()->create();
        $this->admin   = User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'fecha'              => now()->toDateString(),
            'moneda_base_id'     => $this->usd->id,
            'moneda_cotizada_id' => $this->ves->id,
            'tasa_compra'        => 36.20,
            'tasa_venta'         => 36.50,
            'notas'              => null,
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. publicar crea tasa y cierra la anterior
    // ─────────────────────────────────────────────────────────────────────────
    public function test_publicar_crea_tasa_y_cierra_anterior(): void
    {
        $primera = $this->service->publicar($this->payload(), $this->admin);
        $this->assertNull($primera->vigente_hasta);

        sleep(1); // asegurar diferencia de timestamp
        $segunda = $this->service->publicar($this->payload(['tasa_venta' => 36.80]), $this->admin);

        $primera->refresh();
        $this->assertNotNull($primera->vigente_hasta);
        $this->assertNull($segunda->vigente_hasta);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. publicar lanza excepción si venta < compra sin notas
    // ─────────────────────────────────────────────────────────────────────────
    public function test_publicar_rechaza_venta_menor_que_compra_sin_notas(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->publicar($this->payload([
            'tasa_compra' => 36.50,
            'tasa_venta'  => 36.20,
        ]), $this->admin);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. publicar acepta excepción con notas suficientes
    // ─────────────────────────────────────────────────────────────────────────
    public function test_publicar_acepta_excepcion_con_notas(): void
    {
        $tasa = $this->service->publicar($this->payload([
            'tasa_compra' => 36.50,
            'tasa_venta'  => 36.20,
            'notas'       => 'Inversión intencional para prueba de ajuste de mercado especial',
        ]), $this->admin);

        $this->assertInstanceOf(TasaDiaria::class, $tasa);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. obtenerVigente devuelve la tasa activa
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_vigente_devuelve_tasa_activa(): void
    {
        $tasa = $this->service->publicar($this->payload(), $this->admin);

        $vigente = $this->service->obtenerVigente($this->usd->id, $this->ves->id);

        $this->assertNotNull($vigente);
        $this->assertEquals($tasa->id, $vigente->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. obtenerVigente retorna null si no hay tasa
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_vigente_retorna_null_si_no_hay_tasa(): void
    {
        $resultado = $this->service->obtenerVigente($this->usd->id, $this->ves->id);

        $this->assertNull($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. obtenerUltimaPublicada devuelve la más reciente aunque esté cerrada
    // ─────────────────────────────────────────────────────────────────────────
    public function test_obtener_ultima_publicada_devuelve_la_mas_reciente(): void
    {
        $this->service->publicar($this->payload(), $this->admin);
        $segunda = $this->service->publicar($this->payload(['tasa_venta' => 36.90]), $this->admin);

        $ultima = $this->service->obtenerUltimaPublicada($this->usd->id, $this->ves->id);

        $this->assertEquals($segunda->id, $ultima->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. validarTasaEfectiva venta favorable e desfavorable
    // ─────────────────────────────────────────────────────────────────────────
    public function test_validar_tasa_efectiva_venta(): void
    {
        // Mínimo de venta = 36.50 → por debajo es desfavorable.
        $tasa = $this->service->publicar($this->payload(['tasa_venta_minima' => 36.50]), $this->admin);

        $this->assertTrue($this->service->validarTasaEfectiva($tasa, 36.50, 'venta')['es_valida']);  // igual ✓
        $this->assertTrue($this->service->validarTasaEfectiva($tasa, 37.00, 'venta')['es_valida']);  // mayor ✓

        $resultado = $this->service->validarTasaEfectiva($tasa, 36.00, 'venta');                     // menor ✗
        $this->assertFalse($resultado['es_valida']);
        $this->assertTrue($resultado['es_desfavorable']);
        $this->assertTrue($resultado['requiere_justificacion']);
        $this->assertNotNull($resultado['mensaje']);
    }

    public function test_validar_tasa_efectiva_sin_minimo_siempre_valida(): void
    {
        // Sin mínimos configurados → nunca desfavorable.
        $tasa = $this->service->publicar($this->payload(), $this->admin);

        $this->assertTrue($this->service->validarTasaEfectiva($tasa, 1.00, 'venta')['es_valida']);
        $this->assertTrue($this->service->validarTasaEfectiva($tasa, 9999.0, 'compra')['es_valida']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. validarTasaEfectiva compra favorable e desfavorable
    // ─────────────────────────────────────────────────────────────────────────
    public function test_validar_tasa_efectiva_compra(): void
    {
        // Mínimo de compra = 36.20 → por encima es desfavorable (la casa paga más).
        $tasa = $this->service->publicar($this->payload(['tasa_compra_minima' => 36.20]), $this->admin);

        $this->assertTrue($this->service->validarTasaEfectiva($tasa, 36.20, 'compra')['es_valida']);  // igual ✓
        $this->assertTrue($this->service->validarTasaEfectiva($tasa, 36.00, 'compra')['es_valida']);  // menor (paga menos) ✓

        $resultado = $this->service->validarTasaEfectiva($tasa, 36.50, 'compra');                     // mayor ✗
        $this->assertFalse($resultado['es_valida']);
        $this->assertTrue($resultado['es_desfavorable']);
        $this->assertTrue($resultado['requiere_justificacion']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. identificarPar con VES siempre retorna (extranjera, VES)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_identificar_par_venta_retorna_usd_ves(): void
    {
        $usdt = Moneda::factory()->create(['codigo' => 'USDT']);

        $movs = [
            ['moneda_id' => $this->usd->id, 'monto' => -100.0],
            ['moneda_id' => $this->ves->id, 'monto' =>  3650.0],
        ];

        $par = $this->service->identificarPar($movs);

        $this->assertEquals($this->usd->id, $par['moneda_base_id']);
        $this->assertEquals($this->ves->id, $par['moneda_cotizada_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. identificarPar compra retorna mismo par que venta (USD, VES)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_identificar_par_compra_retorna_usd_ves(): void
    {
        $movs = [
            ['moneda_id' => $this->usd->id, 'monto' =>  100.0],
            ['moneda_id' => $this->ves->id, 'monto' => -3620.0],
        ];

        $par = $this->service->identificarPar($movs);

        $this->assertEquals($this->usd->id, $par['moneda_base_id']);
        $this->assertEquals($this->ves->id, $par['moneda_cotizada_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. identificarPar con 3+ monedas lanza ValidationException
    // ─────────────────────────────────────────────────────────────────────────
    public function test_identificar_par_tres_monedas_lanza_excepcion(): void
    {
        $usdt = Moneda::factory()->create(['codigo' => 'USDT']);

        $movs = [
            ['moneda_id' => $this->usd->id,  'monto' => -100.0],
            ['moneda_id' => $this->ves->id,  'monto' =>  3650.0],
            ['moneda_id' => $usdt->id,       'monto' =>  50.0],
        ];

        $this->expectException(ValidationException::class);
        $this->service->identificarPar($movs);
    }
}
