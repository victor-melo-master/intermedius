<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BancoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'codigo' => fake()->optional()->numerify('####'),
            'pais'   => 'VE',
            'activo' => true,
        ];
    }
}
