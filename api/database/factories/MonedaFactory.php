<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MonedaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo'    => strtoupper(fake()->unique()->lexify('???')),
            'nombre'    => fake()->word(),
            'simbolo'   => fake()->optional()->lexify('?'),
            'es_fiat'   => true,
            'es_cripto' => false,
            'decimales' => 2,
            'activa'    => true,
        ];
    }

    public function usd(): static
    {
        return $this->state(['codigo' => 'USD', 'nombre' => 'Dólar Estadounidense', 'simbolo' => '$', 'es_fiat' => true, 'es_cripto' => false, 'decimales' => 2]);
    }

    public function ves(): static
    {
        return $this->state(['codigo' => 'VES', 'nombre' => 'Bolívar Venezolano', 'simbolo' => 'Bs.', 'es_fiat' => true, 'es_cripto' => false, 'decimales' => 2]);
    }

    public function usdt(): static
    {
        return $this->state(['codigo' => 'USDT', 'nombre' => 'Tether USD', 'simbolo' => '₮', 'es_fiat' => false, 'es_cripto' => true, 'decimales' => 6]);
    }
}
