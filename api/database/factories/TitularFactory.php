<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TitularFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'alias'  => fake()->optional()->word(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
