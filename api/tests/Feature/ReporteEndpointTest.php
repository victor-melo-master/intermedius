<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReporteEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);
        Storage::disk('local');

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->create(['activo' => true]);
        $this->operador->assignRole('operador');
    }

    private function crearReporte(string $dir, string $nombre): string
    {
        Storage::makeDirectory($dir);
        $path = "{$dir}/{$nombre}";
        Storage::put($path, '%PDF-1.4 test');
        $this->assertTrue(Storage::exists($path));

        return $path;
    }

    // ── descargar ────────────────────────────────────────────────────

    public function test_descargar_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/reportes/descargar?path=reportes/comisiones/x.pdf')
            ->assertStatus(401);
    }

    public function test_descargar_deniega_sin_rol(): void
    {
        $this->actingAs($this->operador)
            ->getJson('/api/v1/reportes/descargar?path=reportes/comisiones/x.pdf')
            ->assertStatus(403);
    }

    public function test_descargar_ok_con_admin(): void
    {
        $path = $this->crearReporte('reportes/comisiones', 'comisiones_operadores_2026-08.pdf');

        $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/descargar?path=' . urlencode($path))
            ->assertStatus(200)
            ->assertHeader('content-disposition', 'attachment; filename=comisiones_operadores_2026-08.pdf');
    }

    public function test_descargar_404_si_no_existe(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/descargar?path=reportes/comisiones/inexistente.pdf')
            ->assertStatus(404);
    }

    public function test_descargar_bloquea_path_traversal(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/descargar?path=../../.env')
            ->assertStatus(404);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/descargar?path=../reportes/comisiones/x.pdf')
            ->assertStatus(404);
    }

    public function test_descargar_valida_path_requerido(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/descargar')
            ->assertStatus(422);
    }

    // ── historico ────────────────────────────────────────────────────

    public function test_historico_lista_comisiones_y_resumen(): void
    {
        $this->crearReporte('reportes/comisiones', 'comisiones_operadores_2026-08.pdf');
        $this->crearReporte('reportes/resumen', 'resumen_operativo_2026-08.xlsx');

        $res = $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/historico')
            ->assertStatus(200)
            ->json('data');

        $tipos = collect($res)->pluck('tipo')->unique()->sort()->values()->all();
        $formatos = collect($res)->pluck('formato')->unique()->sort()->values()->all();

        $this->assertEquals(['comisiones', 'resumen'], $tipos);
        $this->assertEquals(['pdf', 'xlsx'], $formatos);
    }

    // ── resumen ──────────────────────────────────────────────────────

    public function test_index_resumen_retorna_estructura(): void
    {
        $res = $this->actingAs($this->admin)
            ->getJson('/api/v1/reportes/resumen?fecha_desde=2026-08-01&fecha_hasta=2026-08-07')
            ->assertStatus(200)
            ->json();

        $this->assertArrayHasKey('periodo', $res);
        $this->assertArrayHasKey('operaciones', $res);
        $this->assertArrayHasKey('volumenes', $res);
        $this->assertArrayHasKey('ganancias', $res);
        $this->assertArrayHasKey('por_operador', $res);
        $this->assertArrayHasKey('efectivo_pendiente', $res);
    }

    public function test_exportar_resumen_excel(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson('/api/v1/reportes/resumen/exportar', [
                'fecha_desde' => '2026-08-01',
                'fecha_hasta' => '2026-08-07',
                'formato'     => 'excel',
            ])
            ->assertStatus(200)
            ->json('data');

        $this->assertEquals('excel', $res['formato']);
        $this->assertTrue(Storage::exists($res['path']));
        Storage::delete($res['path']);
    }

    public function test_exportar_resumen_pdf(): void
    {
        $res = $this->actingAs($this->admin)
            ->postJson('/api/v1/reportes/resumen/exportar', [
                'fecha_desde' => '2026-08-01',
                'fecha_hasta' => '2026-08-07',
                'formato'     => 'pdf',
            ])
            ->assertStatus(200)
            ->json('data');

        $this->assertEquals('pdf', $res['formato']);
        $this->assertTrue(Storage::exists($res['path']));
        Storage::delete($res['path']);
    }

    public function test_exportar_resumen_valida_formato(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/reportes/resumen/exportar', [
                'fecha_desde' => '2026-08-01',
                'fecha_hasta' => '2026-08-07',
                'formato'     => 'csv',
            ])
            ->assertStatus(422);
    }
}
