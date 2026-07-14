<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

#[IgnoreDeprecations]
class TasaDiariaEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;
    private Moneda $usd;
    private Moneda $ves;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->usd = Moneda::where('codigo', 'USD')->first();
        $this->ves = Moneda::where('codigo', 'VES')->first();

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'fecha'              => now()->toDateString(),
            'moneda_base_id'     => $this->usd->id,
            'moneda_cotizada_id' => $this->ves->id,
            'tasa_compra'        => 36.20,
            'tasa_venta'         => 36.50,
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Admin puede publicar tasa
    // ─────────────────────────────────────────────────────────────────────────
    public function test_admin_puede_publicar_tasa(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/tasas-diarias', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.tasa_venta', '36.50000000');

        $this->assertDatabaseHas('tasas_diarias', ['tasa_venta' => 36.50]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Operador no puede publicar tasa
    // ─────────────────────────────────────────────────────────────────────────
    public function test_operador_no_puede_publicar_tasa(): void
    {
        $this->actingAs($this->operador, 'sanctum')
            ->postJson('/api/v1/configuracion/tasas-diarias', $this->payload())
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Publicar segunda tasa cierra la anterior
    // ─────────────────────────────────────────────────────────────────────────
    public function test_publicar_segunda_tasa_cierra_anterior(): void
    {
        $primera = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/tasas-diarias', $this->payload())
            ->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/tasas-diarias', $this->payload(['tasa_venta' => 37.00]));

        $this->assertDatabaseMissing('tasas_diarias', ['id' => $primera, 'vigente_hasta' => null]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. vigentes retorna las actualmente vigentes
    // ─────────────────────────────────────────────────────────────────────────
    public function test_vigentes_retorna_tasas_activas(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/tasas-diarias', $this->payload());

        $response = $this->actingAs($this->operador, 'sanctum')
            ->getJson('/api/v1/configuracion/tasas-vigentes');

        $response->assertOk()
            ->assertJsonPath('data.0.par', 'USD/VES');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Validación: tasa_venta < tasa_compra sin notas falla
    // ─────────────────────────────────────────────────────────────────────────
    public function test_venta_menor_que_compra_sin_notas_falla(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/configuracion/tasas-diarias', $this->payload([
                'tasa_compra' => 36.50,
                'tasa_venta'  => 36.20,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tasa_venta']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Sin autenticación falla
    // ─────────────────────────────────────────────────────────────────────────
    public function test_sin_autenticacion_falla(): void
    {
        $this->getJson('/api/v1/configuracion/tasas-vigentes')
            ->assertUnauthorized();
    }
}
