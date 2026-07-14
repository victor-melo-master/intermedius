<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\Titular;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GastoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;
    private CategoriaGasto $categoria;
    private Cuenta $cuentaUsd;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed(CatalogosBaseSeeder::class);

        $usd     = Moneda::where('codigo', 'USD')->first();
        $titular = Titular::factory()->create();

        $this->cuentaUsd = Cuenta::factory()->create([
            'moneda_id'  => $usd->id,
            'titular_id' => $titular->id,
            'tipo'       => 'plataforma',
            'activa'     => true,
        ]);

        $this->categoria = CategoriaGasto::create([
            'nombre'     => 'Servicios',
            'titular_id' => $titular->id,
            'activa'     => true,
        ]);

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    private function payloadGasto(array $overrides = []): array
    {
        return array_merge([
            'fecha'              => '2026-05-11',
            'categoria_gasto_id' => $this->categoria->id,
            'operador_id'        => $this->operador->id,
            'descripcion'        => 'Pago internet oficina',
            'movimientos'        => [
                ['cuenta_id' => $this->cuentaUsd->id, 'monto' => -50.0, 'tasa_a_usd' => 1.0],
            ],
        ], $overrides);
    }

    public function test_crear_gasto_con_categoria_y_un_movimiento(): void
    {
        $response = $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/gastos', $this->payloadGasto());

        $response->assertCreated()
            ->assertJsonPath('data.estatus', 'sin_verificar');

        $this->assertDatabaseHas('operaciones', ['descripcion' => 'Pago internet oficina']);

        $op = Operacion::with('tipoOperacion')->first();
        $this->assertEquals('gasto', $op->tipoOperacion->codigo);
        $this->assertEquals($this->categoria->id, $op->categoria_gasto_id);
        $this->assertCount(1, $op->movimientos);
    }

    public function test_gasto_sin_categoria_falla(): void
    {
        $payload = $this->payloadGasto();
        unset($payload['categoria_gasto_id']);

        $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/gastos', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria_gasto_id']);
    }

    public function test_gasto_sin_movimientos_falla(): void
    {
        $payload = $this->payloadGasto(['movimientos' => []]);

        $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/gastos', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['movimientos']);
    }

    public function test_gasto_falla_sin_autenticacion(): void
    {
        $this->postJson('/api/v1/gastos', $this->payloadGasto())
            ->assertUnauthorized();
    }

    public function test_index_de_gastos_solo_retorna_tipo_gasto(): void
    {
        // Crear un gasto
        $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/gastos', $this->payloadGasto());

        // Crear una operación de otro tipo (no gasto)
        $tipoCambio = TipoOperacion::where('codigo', 'cambio')->first();
        Operacion::factory()->create([
            'tipo_operacion_id' => $tipoCambio->id,
            'operador_id'       => $this->operador->id,
        ]);

        $response = $this->actingAs($this->operador, 'sanctum')
            ->getJson('/api/v1/gastos');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('gasto', $data[0]['tipo_operacion']['codigo']);
    }
}
