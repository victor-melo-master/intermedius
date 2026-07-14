<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Titular;
use App\Models\TipoOperacion;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[IgnoreDeprecations]
class ComisionConfigEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;
    private Moneda $usd;
    private Cuenta $cuenta;
    private Titular $titular;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->usd     = Moneda::where('codigo', 'USD')->first();
        $this->titular = Titular::factory()->create();
        $this->cuenta  = Cuenta::factory()->create([
            'moneda_id'  => $this->usd->id,
            'titular_id' => $this->titular->id,
            'activa'     => true,
        ]);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ComisionCuenta
    // ─────────────────────────────────────────────────────────────────────────
    public function test_admin_puede_crear_comision_cuenta(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/comisiones-cuenta', [
                'cuenta_id'     => $this->cuenta->id,
                'descripcion'   => 'Comisión de transferencia',
                'tipo_calculo'  => 'porcentaje',
                'valor'         => 1.5,
                'moneda_id'     => $this->usd->id,
                'aplica_a'      => 'egreso',
                'vigente_desde' => now()->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.tipo_calculo', 'porcentaje');
    }

    public function test_operador_no_puede_crear_comision_cuenta(): void
    {
        $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/configuracion/comisiones-cuenta', [
                'cuenta_id'     => $this->cuenta->id,
                'descripcion'   => 'Test',
                'tipo_calculo'  => 'porcentaje',
                'valor'         => 1.5,
                'moneda_id'     => $this->usd->id,
                'aplica_a'      => 'egreso',
                'vigente_desde' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_comision_cuenta_sin_cuenta_id_ni_banco_id_falla(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/comisiones-cuenta', [
                'descripcion'   => 'Test',
                'tipo_calculo'  => 'porcentaje',
                'valor'         => 1.5,
                'moneda_id'     => $this->usd->id,
                'aplica_a'      => 'egreso',
                'vigente_desde' => now()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cuenta_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ComisionOperador
    // ─────────────────────────────────────────────────────────────────────────
    public function test_admin_puede_crear_comision_operador(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/comisiones-operador', [
                'titular_id'    => $this->titular->id,
                'descripcion'   => 'Comisión mensual operador',
                'tipo_calculo'  => 'monto_fijo',
                'valor'         => 50.0,
                'moneda_id'     => $this->usd->id,
                'base_calculo'  => 'monto_operacion',
                'vigente_desde' => now()->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.tipo_calculo', 'monto_fijo');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ComisionMetodoPago
    // ─────────────────────────────────────────────────────────────────────────
    public function test_admin_puede_crear_comision_metodo_pago(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/comisiones-metodo-pago', [
                'nombre_metodo' => 'Zelle',
                'descripcion'   => 'Fee Zelle',
                'tipo_calculo'  => 'porcentaje',
                'valor'         => 2.0,
                'moneda_id'     => $this->usd->id,
                'vigente_desde' => now()->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre_metodo', 'Zelle');
    }
}
