<?php

namespace Database\Factories;

use App\Models\TipoOperacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OperacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fecha'                  => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'tipo_operacion_id'      => TipoOperacion::factory(),
            'cliente_id'             => null,
            'categoria_gasto_id'     => null,
            'operador_id'            => User::factory(),
            'tasa_aplicada'          => null,
            'tasa_mercado_snapshot'  => null,
            'fuente_tasa_mercado'    => null,
            'tasa_sugerida'          => null,
            'tasa_diaria_id'         => null,
            'sin_tasa_referencia'    => false,
            'ganancia_bruta_usd'     => 0,
            'ganancia_real_usd'      => null,
            'ganancia_bruta_ves'     => 0,
            'ganancia_real_ves'      => null,
            'total_comisiones_usd'   => 0,
            'total_comisiones_ves'   => 0,
            'ganancia_neta_usd'      => 0,
            'ganancia_neta_ves'      => 0,
            'referencia'             => null,
            'descripcion'            => null,
            'estatus'                => 'sin_verificar',
            'origen'                 => 'manual',
            'origen_referencia'      => null,
        ];
    }
}
