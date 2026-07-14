<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'operacion_id'          => Operacion::factory(),
            'cuenta_id'             => Cuenta::factory(),
            'moneda_id'             => Moneda::factory(),
            'monto'                 => fake()->randomFloat(4, 1, 10000),
            'tasa_a_usd'            => 1,
            'monto_usd_equivalente' => fake()->randomFloat(4, 1, 10000),
            'orden'                 => fake()->numberBetween(1, 10),
        ];
    }
}
