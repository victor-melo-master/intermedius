<?php

namespace Database\Factories;

use App\Models\Moneda;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TasaDiariaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fecha'              => today()->toDateString(),
            'moneda_base_id'     => Moneda::factory(),
            'moneda_cotizada_id' => Moneda::factory(),
            'tasa_compra'        => fake()->randomFloat(4, 30, 40),
            'tasa_venta'         => fake()->randomFloat(4, 30, 41),
            'definida_por_id'    => User::factory(),
            'vigente_desde'      => now()->subDay(),
        ];
    }
}
