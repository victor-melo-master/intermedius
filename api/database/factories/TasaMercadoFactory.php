<?php

namespace Database\Factories;

use App\Models\Moneda;
use Illuminate\Database\Eloquent\Factories\Factory;

class TasaMercadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fuente'           => fake()->randomElement(['bcv', 'binance_p2p_buy', 'binance_p2p_sell']),
            'moneda_base_id'   => Moneda::factory(),
            'moneda_cotizada_id' => Moneda::factory(),
            'valor'            => fake()->randomFloat(4, 30, 40),
            'capturado_en'     => now()->subMinutes(fake()->numberBetween(1, 60)),
        ];
    }
}
