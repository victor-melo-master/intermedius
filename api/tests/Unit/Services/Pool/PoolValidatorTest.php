<?php

namespace Tests\Unit\Services\Pool;

use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\User;
use App\Services\Pool\PoolValidator;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoolValidatorTest extends TestCase
{
    use RefreshDatabase;

    private PoolValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);
        $this->validator = new PoolValidator();
    }

    public function test_assert_puede_tomar_operaciones_pasa_con_permiso()
    {
        $pagador = User::factory()->create(['activo' => true]);
        $pagador->assignRole('pagador');

        $this->validator->assertPuedeTomarOperaciones($pagador);

        $this->assertTrue(true);
    }

    public function test_assert_puede_tomar_operaciones_lanza_excepcion_sin_permiso()
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('lectura');

        $this->validator->assertPuedeTomarOperaciones($user);
    }

    public function test_assert_puede_soltar_pasa_con_estado_asignada_y_mismo_pagador()
    {
        $pagador = User::factory()->create(['activo' => true]);
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'estado' => 'en_proceso',
            'pagador_id' => $pagador->id,
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $pagador->id,
        ]);

        $this->validator->assertPuedeSoltar($operacion, $pagador);

        $this->assertTrue(true);
    }

    public function test_assert_puede_soltar_lanza_excepcion_si_no_esta_asignada()
    {
        $this->expectException(ValidationException::class);

        $pagador = User::factory()->create(['activo' => true]);
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'pendiente',
            'estado' => 'en_espera',
            'pagador_id' => null,
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $pagador->id,
        ]);

        $this->validator->assertPuedeSoltar($operacion, $pagador);
    }

    public function test_assert_puede_soltar_lanza_excepcion_si_otro_pagador()
    {
        $this->expectException(ValidationException::class);

        $pagador = User::factory()->create(['activo' => true]);
        $otroPagador = User::factory()->create(['activo' => true]);
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'estado' => 'en_proceso',
            'pagador_id' => $otroPagador->id,
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $pagador->id,
        ]);

        $this->validator->assertPuedeSoltar($operacion, $pagador);
    }

    public function test_assert_puede_pagar_pasa_con_estado_asignada_mismo_pagador_y_permiso()
    {
        $pagador = User::factory()->create(['activo' => true]);
        $pagador->assignRole('pagador');
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'estado' => 'en_proceso',
            'pagador_id' => $pagador->id,
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $pagador->id,
        ]);

        $this->validator->assertPuedePagar($operacion, $pagador);

        $this->assertTrue(true);
    }

    public function test_assert_puede_pagar_lanza_excepcion_sin_permiso_pagar()
    {
        $this->expectException(ValidationException::class);

        $operador = User::factory()->create(['activo' => true]);
        $operador->assignRole('operador');
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'estado' => 'en_proceso',
            'pagador_id' => $operador->id,
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $operador->id,
        ]);

        $this->validator->assertPuedePagar($operacion, $operador);
    }

    public function test_assert_todas_transacciones_validadas_pasa_si_no_hay_pendientes()
    {
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'tipo_operacion_id' => $tipo->id,
        ]);

        $this->validator->assertTodasTransaccionesValidadas($operacion);

        $this->assertTrue(true);
    }

    public function test_assert_puede_cancelar_pasa_con_estado_pendiente_y_permiso()
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('admin');
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'pendiente',
            'estado' => 'en_espera',
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $admin->id,
        ]);

        $this->validator->assertPuedeCancelar($operacion, $admin);

        $this->assertTrue(true);
    }

    public function test_assert_puede_cancelar_lanza_excepcion_sin_permiso()
    {
        $this->expectException(ValidationException::class);

        $lectura = User::factory()->create(['activo' => true]);
        $lectura->assignRole('lectura');
        $tipo = TipoOperacion::where('codigo', 'cambio')->first();
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'pendiente',
            'estado' => 'en_espera',
            'tipo_operacion_id' => $tipo->id,
            'operador_id' => $lectura->id,
        ]);

        $this->validator->assertPuedeCancelar($operacion, $lectura);
    }
}
