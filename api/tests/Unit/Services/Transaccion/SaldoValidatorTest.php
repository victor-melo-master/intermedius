<?php

namespace Tests\Unit\Services\Transaccion;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Movimiento;
use App\Models\Titular;
use App\Models\Transaccion;
use App\Services\Transaccion\SaldoValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

#[IgnoreDeprecations]
class SaldoValidatorTest extends TestCase
{
    use RefreshDatabase;

    private SaldoValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SaldoValidator();
    }

    public function test_assert_saldo_suficiente_pasa_con_saldo_suficiente()
    {
        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 100,
            'saldo_cache_at' => now(),
        ]);

        $this->validator->assertSaldoSuficiente($cuenta->id, $moneda->id, 50);

        $this->assertTrue(true);
    }

    public function test_assert_saldo_suficiente_lanza_excepcion_cuando_saldo_insuficiente()
    {
        $this->expectException(ValidationException::class);

        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 50,
            'saldo_cache_at' => now(),
        ]);

        $this->validator->assertSaldoSuficiente($cuenta->id, $moneda->id, 100);
    }

    public function test_obtener_saldo_disponible_usa_cache_si_es_reciente()
    {
        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 100,
            'saldo_cache_at' => now()->subMinutes(2),
        ]);

        $saldo = $this->validator->obtenerSaldoDisponible($cuenta, $moneda->id);

        $this->assertEquals(100, $saldo);
    }

    public function test_obtener_saldo_disponible_calcula_en_vivo_si_cache_antiguo()
    {
        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 100,
            'saldo_cache_at' => now()->subMinutes(10),
        ]);

        Movimiento::factory()->create([
            'cuenta_id' => $cuenta->id,
            'moneda_id' => $moneda->id,
            'monto' => 200,
        ]);

        Transaccion::factory()->create([
            'cuenta_origen_id' => $cuenta->id,
            'moneda_id' => $moneda->id,
            'monto' => 50,
            'estado' => 'pendiente',
        ]);

        $saldo = $this->validator->obtenerSaldoDisponible($cuenta, $moneda->id);

        $this->assertEquals(150, $saldo);
    }

    public function test_assert_saldo_suficiente_retorna_si_monto_es_cero()
    {
        $moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $cuenta = Cuenta::factory()->create([
            'moneda_id' => $moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 0,
            'saldo_cache_at' => now(),
        ]);

        $this->validator->assertSaldoSuficiente($cuenta->id, $moneda->id, 0);

        $this->assertTrue(true);
    }
}
