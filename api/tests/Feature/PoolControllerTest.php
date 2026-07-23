<?php

namespace Tests\Feature;

use App\Models\Operacion;
use App\Models\TipoOperacion;
use App\Models\User;
use Database\Seeders\CatalogosBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $pagador;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosBaseSeeder::class);

        $this->pagador = User::factory()->create(['email' => 'pagador@test.com']);
        $this->pagador->assignRole('pagador');

        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('admin');
    }

    public function test_index_lista_ordenes_pendientes(): void
    {
        $compraUsd = TipoOperacion::where('codigo', 'compra_usd')->first();
        $asignada = Operacion::factory()->create(['estado_pool' => 'asignada', 'tipo_operacion_id' => $compraUsd->id]);
        $pendiente = Operacion::factory()->create(['estado_pool' => 'pendiente', 'tipo_operacion_id' => $compraUsd->id]);

        $response = $this->actingAs($this->pagador)
            ->getJson('/api/v1/pool');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendiente->id);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/pool');
        $response->assertStatus(401);
    }

    public function test_index_pagina_con_per_page(): void
    {
        $compraUsd = TipoOperacion::where('codigo', 'compra_usd')->first();
        Operacion::factory()->count(5)->create(['estado_pool' => 'pendiente', 'tipo_operacion_id' => $compraUsd->id]);

        $response = $this->actingAs($this->pagador)
            ->getJson('/api/v1/pool?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_mis_ordenes_solo_asignadas_al_usuario(): void
    {
        $compraUsd = TipoOperacion::where('codigo', 'compra_usd')->first();
        $propia = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->pagador->id,
            'tipo_operacion_id' => $compraUsd->id,
        ]);
        $deOtro = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->admin->id,
            'tipo_operacion_id' => $compraUsd->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->getJson('/api/v1/pool/mis-ordenes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $propia->id);
    }

    public function test_tomar_asigna_orden_al_pagador(): void
    {
        $operacion = Operacion::factory()->create(['estado_pool' => 'pendiente']);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/tomar");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $operacion->id)
            ->assertJsonPath('data.estado_pool', 'asignada')
            ->assertJsonPath('data.pagador_id', $this->pagador->id)
            ->assertJsonStructure(['data' => ['id', 'estado_pool', 'pagador_id', 'asignada_at']]);
    }

    public function test_tomar_ya_asignada_da_422(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->admin->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/tomar");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Esta orden ya fue tomada por otro pagador.']);
    }

    public function test_soltar_libera_orden_asignada(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->pagador->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/soltar");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado_pool', 'pendiente')
            ->assertJsonPath('data.pagador_id', null);
    }

    public function test_soltar_pendiente_sin_pagador_da_403(): void
    {
        $operacion = Operacion::factory()->create(['estado_pool' => 'pendiente']);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/soltar");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Solo puede soltar órdenes asignadas a usted.']);
    }

    public function test_soltar_asignada_a_otro_da_422_por_no_ser_del_pagador(): void
    {
        $otro = User::factory()->create();
        $otro->assignRole('pagador');
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $otro->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/soltar");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Solo puede soltar órdenes asignadas a usted.']);
    }

    public function test_soltar_orden_de_otro_pagador_da_403(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->admin->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/soltar");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Solo puede soltar órdenes asignadas a usted.']);
    }

    public function test_marcar_pagada_ordena_asignada(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->pagador->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/pagar");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado_pool', 'pagada')
            ->assertJsonStructure(['data' => ['pagada_at']]);
    }

    public function test_marcar_pagada_pendiente_sin_pagador_da_403(): void
    {
        $operacion = Operacion::factory()->create(['estado_pool' => 'pendiente']);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/pagar");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Solo puede pagar órdenes asignadas a usted.']);
    }

    public function test_marcar_pagada_de_otro_pagador_da_403(): void
    {
        $otro = User::factory()->create();
        $otro->assignRole('pagador');
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $otro->id,
        ]);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/pagar");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Solo puede pagar órdenes asignadas a usted.']);
    }

    public function test_cancelar_requiere_motivo(): void
    {
        $operacion = Operacion::factory()->create(['estado_pool' => 'pendiente']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/pool/{$operacion->id}/cancelar", []);

        $response->assertStatus(422);
    }

    public function test_cancelar_ordena_por_admin(): void
    {
        $operacion = Operacion::factory()->create(['estado_pool' => 'pendiente']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/pool/{$operacion->id}/cancelar", [
                'motivo_cancelacion' => 'Prueba de cancelación.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado_pool', 'cancelada');
    }

    public function test_cancelar_pagador_no_autorizado(): void
    {
        $operacion = Operacion::factory()->create(['estado_pool' => 'pendiente']);

        $response = $this->actingAs($this->pagador)
            ->postJson("/api/v1/pool/{$operacion->id}/cancelar", [
                'motivo_cancelacion' => 'No debería poder.',
            ]);

        $response->assertStatus(403);
    }

    public function test_cancelar_ya_cancelada_da_422(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool'        => 'cancelada',
            'motivo_cancelacion' => 'Ya cancelada.',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/pool/{$operacion->id}/cancelar", [
                'motivo_cancelacion' => 'Otra vez.',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Esta orden ya está cancelada.']);
    }

    public function test_admin_puede_soltar_orden_de_cualquier_pagador(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->pagador->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/pool/{$operacion->id}/soltar");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado_pool', 'pendiente');
    }

    public function test_admin_puede_pagar_orden_de_cualquier_pagador(): void
    {
        $operacion = Operacion::factory()->create([
            'estado_pool' => 'asignada',
            'pagador_id'  => $this->pagador->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/pool/{$operacion->id}/pagar");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado_pool', 'pagada');
    }
}
