<?php

namespace Tests\Unit\Services\Transaccion;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use App\Services\Transaccion\SaldoValidator;
use App\Services\Transaccion\TransaccionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransaccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransaccionService $service;
    private Moneda $moneda;
    private Cuenta $cuentaOrigen;
    private Cuenta $cuentaDestino;
    private Operacion $operacion;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moneda = Moneda::factory()->create();
        $titular = Titular::factory()->create();
        $this->cuentaOrigen = Cuenta::factory()->create([
            'moneda_id' => $this->moneda->id,
            'titular_id' => $titular->id,
            'saldo_cache' => 1000,
            'saldo_cache_at' => now(),
        ]);
        $this->cuentaDestino = Cuenta::factory()->create([
            'moneda_id' => $this->moneda->id,
            'titular_id' => $titular->id,
        ]);
        $this->operador = User::factory()->create();
        $tipo = TipoOperacion::factory()->cambio()->create();
        $this->operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $this->operador->id,
        ]);

        $saldoValidator = new SaldoValidator();
        $this->service = new TransaccionService($saldoValidator);
    }

    public function test_crear_transacciones_crea_transacciones_pendientes()
    {
        $transacciones = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ]);

        $this->assertCount(1, $transacciones);
        $this->assertEquals('pendiente', $transacciones->first()->estado);
        $this->assertEquals(100, (float) $transacciones->first()->monto);
    }

    public function test_crear_transacciones_lanza_excepcion_si_saldo_insuficiente()
    {
        $this->expectException(ValidationException::class);

        $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 999999,
            ],
        ]);
    }

    public function test_validar_transaccion_cambia_estado_a_validada()
    {
        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $resultado = $this->service->validarTransaccion($transaccion, $this->operador);

        $this->assertEquals('validada', $resultado->estado);
        $this->assertEquals($this->operador->id, $resultado->validada_por_id);
        $this->assertNotNull($resultado->validada_en);
    }

    public function test_validar_transaccion_lanza_excepcion_si_no_esta_pendiente()
    {
        $this->expectException(ValidationException::class);

        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $this->service->validarTransaccion($transaccion, $this->operador);
        $this->service->validarTransaccion($transaccion, $this->operador);
    }

    public function test_rechazar_transaccion_cambia_estado_a_rechazada()
    {
        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $resultado = $this->service->rechazarTransaccion($transaccion, 'Fondos insuficientes');

        $this->assertEquals('rechazada', $resultado->estado);
        $this->assertEquals('Fondos insuficientes', $resultado->motivo_rechazo);
    }

    public function test_rechazar_transaccion_lanza_excepcion_si_no_esta_pendiente()
    {
        $this->expectException(ValidationException::class);

        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $this->service->validarTransaccion($transaccion, $this->operador);
        $this->service->rechazarTransaccion($transaccion, 'Ya validada');
    }

    public function test_cambiar_cuenta_destino_actualiza_cuenta_destino()
    {
        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $nuevaDestino = Cuenta::factory()->create([
            'moneda_id' => $this->moneda->id,
            'titular_id' => $this->cuentaOrigen->titular_id,
        ]);

        $resultado = $this->service->cambiarCuentaDestino($transaccion, $nuevaDestino->id);

        $this->assertEquals($nuevaDestino->id, $resultado->cuenta_destino_id);
    }

    public function test_cambiar_cuenta_destino_lanza_excepcion_si_moneda_no_coincide()
    {
        $this->expectException(ValidationException::class);

        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $otraMoneda = Moneda::factory()->create(['codigo' => 'EUR']);
        $nuevaDestino = Cuenta::factory()->create([
            'moneda_id' => $otraMoneda->id,
            'titular_id' => $this->cuentaOrigen->titular_id,
        ]);

        $this->service->cambiarCuentaDestino($transaccion, $nuevaDestino->id);
    }

    public function test_cambiar_cuenta_origen_actualiza_y_valida_saldo()
    {
        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 50,
            ],
        ])->first();

        $nuevoOrigen = Cuenta::factory()->create([
            'moneda_id' => $this->moneda->id,
            'titular_id' => $this->cuentaOrigen->titular_id,
            'saldo_cache' => 200,
            'saldo_cache_at' => now(),
        ]);

        $resultado = $this->service->cambiarCuentaOrigen($transaccion, $nuevoOrigen->id);

        $this->assertEquals($nuevoOrigen->id, $resultado->cuenta_origen_id);
    }

    public function test_cancelar_transaccion_cambia_estado_a_cancelada()
    {
        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $resultado = $this->service->cancelarTransaccion($transaccion);

        $this->assertEquals('cancelada', $resultado->estado);
    }

    public function test_cancelar_transaccion_lanza_excepcion_si_no_esta_pendiente()
    {
        $this->expectException(ValidationException::class);

        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $this->service->validarTransaccion($transaccion, $this->operador);
        $this->service->cancelarTransaccion($transaccion);
    }

    public function test_adjuntar_comprobante_actualiza_ruta()
    {
        $transaccion = $this->service->crearTransacciones($this->operacion, [
            [
                'cuenta_origen_id' => $this->cuentaOrigen->id,
                'cuenta_destino_id' => $this->cuentaDestino->id,
                'moneda_id' => $this->moneda->id,
                'monto' => 100,
            ],
        ])->first();

        $resultado = $this->service->adjuntarComprobante($transaccion, 'comprobantes/123.pdf');

        $this->assertEquals('comprobantes/123.pdf', $resultado->comprobante);
    }
}
