<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\FlujoCuenta;
use App\Models\Moneda;
use App\Models\User;
use App\Services\FlujoCuentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlujoCuentaTest extends TestCase
{
    use RefreshDatabase;

    private FlujoCuentaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FlujoCuentaService::class);
    }

    private function crearMonedas(): void
    {
        $defs = [
            ['codigo' => 'VES', 'nombre' => 'Bolívar', 'simbolo' => 'Bs', 'es_fiat' => true, 'es_cripto' => false],
            ['codigo' => 'USD', 'nombre' => 'Dólar', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false],
            ['codigo' => 'EUR', 'nombre' => 'Euro', 'simbolo' => '€', 'es_fiat' => true, 'es_cripto' => false],
            ['codigo' => 'COP', 'nombre' => 'Peso Colombiano', 'simbolo' => 'COL$', 'es_fiat' => true, 'es_cripto' => false],
        ];
        foreach ($defs as $d) {
            Moneda::firstOrCreate(['codigo' => $d['codigo']], $d);
        }
    }

    public function test_cliente_nuevo_recibe_4_cuentas_efectivo(): void
    {
        $this->crearMonedas();

        $cliente = Cliente::create([
            'nombre'   => 'Test User',
            'alias'    => 'testuser',
            'documento' => 'V-99999999',
            'activo'   => true,
        ]);

        $cuentas = Cuenta::where('cliente_id', $cliente->id)
            ->where('tipo', 'efectivo')
            ->get();

        $this->assertCount(4, $cuentas);

        $codigos = $cuentas->pluck('moneda.codigo')->sort()->values()->all();
        $this->assertEquals(['COP', 'EUR', 'USD', 'VES'], $codigos);
    }

    public function test_no_se_crea_efectivo_usdt(): void
    {
        $this->crearMonedas();

        $cliente = Cliente::create([
            'nombre'   => 'Test User',
            'alias'    => 'testuser',
            'documento' => 'V-99999999',
            'activo'   => true,
        ]);

        $cuentaUsdt = Cuenta::where('cliente_id', $cliente->id)
            ->where('tipo', 'efectivo')
            ->whereHas('moneda', fn ($q) => $q->where('codigo', 'USDT'))
            ->exists();

        $this->assertFalse($cuentaUsdt);
    }

    public function test_registrar_entrada_flujo(): void
    {
        $moneda = Moneda::firstOrCreate(['codigo' => 'USD'], ['nombre' => 'Dólar', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false]);
        $cliente = Cliente::create(['nombre' => 'Test', 'alias' => 'test', 'documento' => 'V-111', 'activo' => true]);
        $cuenta = Cuenta::create([
            'cliente_id' => $cliente->id,
            'moneda_id'  => $moneda->id,
            'alias'      => 'Test efectivo',
            'tipo'       => 'efectivo',
            'saldo_cache' => 0,
            'activa'     => true,
        ]);

        $flujo = $this->service->registrarEntrada($cuenta, 500.00, $moneda, 'Pago en efectivo');

        $this->assertInstanceOf(FlujoCuenta::class, $flujo);
        $this->assertEquals('entrada', $flujo->tipo);
        $this->assertEquals(500.00, $flujo->monto);
        $this->assertEquals($cuenta->id, $flujo->cuenta_id);
        $this->assertEquals($moneda->id, $flujo->moneda_id);
        $this->assertEquals('Pago en efectivo', $flujo->descripcion);
    }

    public function test_registrar_salida_flujo(): void
    {
        $moneda = Moneda::firstOrCreate(['codigo' => 'USD'], ['nombre' => 'Dólar', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false]);
        $cliente = Cliente::create(['nombre' => 'Test', 'alias' => 'test', 'documento' => 'V-111', 'activo' => true]);
        $cuenta = Cuenta::create([
            'cliente_id' => $cliente->id,
            'moneda_id'  => $moneda->id,
            'alias'      => 'Test efectivo',
            'tipo'       => 'efectivo',
            'saldo_cache' => 0,
            'activa'     => true,
        ]);

        $flujo = $this->service->registrarSalida($cuenta, 200.00, $moneda, 'Devolución');

        $this->assertEquals('salida', $flujo->tipo);
        $this->assertEquals(200.00, $flujo->monto);
    }

    public function test_obtener_saldo_cliente(): void
    {
        $moneda = Moneda::firstOrCreate(['codigo' => 'USD'], ['nombre' => 'Dólar', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false]);
        $cliente = Cliente::create(['nombre' => 'Test', 'alias' => 'test', 'documento' => 'V-111', 'activo' => true]);
        $cuenta = Cuenta::create([
            'cliente_id' => $cliente->id,
            'moneda_id'  => $moneda->id,
            'alias'      => 'Test efectivo',
            'tipo'       => 'efectivo',
            'saldo_cache' => 0,
            'activa'     => true,
        ]);

        $this->service->registrarEntrada($cuenta, 500.00, $moneda);
        $this->service->registrarSalida($cuenta, 200.00, $moneda);
        $this->service->registrarEntrada($cuenta, 100.00, $moneda);

        $saldo = $this->service->obtenerSaldo($cuenta);

        $this->assertEquals(400.00, $saldo);
    }

    public function test_historial_flujos_paginado(): void
    {
        $moneda = Moneda::firstOrCreate(['codigo' => 'USD'], ['nombre' => 'Dólar', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false]);
        $cliente = Cliente::create(['nombre' => 'Test', 'alias' => 'test', 'documento' => 'V-111', 'activo' => true]);
        $cuenta = Cuenta::create([
            'cliente_id' => $cliente->id,
            'moneda_id'  => $moneda->id,
            'alias'      => 'Test efectivo',
            'tipo'       => 'efectivo',
            'saldo_cache' => 0,
            'activa'     => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->service->registrarEntrada($cuenta, 100.00, $moneda, "Pago {$i}");
        }

        $historial = $this->service->obtenerHistorial($cuenta, 3);

        $this->assertEquals(3, $historial->perPage());
        $this->assertEquals(5, $historial->total());
        $this->assertCount(3, $historial->items());
    }

    public function test_eliminar_flujo_por_transaccion(): void
    {
        $moneda = Moneda::firstOrCreate(['codigo' => 'USD'], ['nombre' => 'Dólar', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false]);
        $cliente = Cliente::create(['nombre' => 'Test', 'alias' => 'test', 'documento' => 'V-111', 'activo' => true]);
        $cuenta = Cuenta::create([
            'cliente_id' => $cliente->id,
            'moneda_id'  => $moneda->id,
            'alias'      => 'Test efectivo',
            'tipo'       => 'efectivo',
            'saldo_cache' => 0,
            'activa'     => true,
        ]);

        $flujo = $this->service->registrarEntrada($cuenta, 500.00, $moneda, null, null, null);

        $eliminados = FlujoCuenta::where('cuenta_id', $cuenta->id)->delete();

        $this->assertEquals(1, $eliminados);
        $this->assertEquals(0, FlujoCuenta::where('cuenta_id', $cuenta->id)->count());
    }
}
