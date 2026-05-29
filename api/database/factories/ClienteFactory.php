<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre'    => fake()->name(),
            'alias'     => fake()->optional()->userName(),
            'documento' => fake()->optional()->numerify('V-########'),
            'telefono'  => fake()->optional()->phoneNumber(),
            'email'     => fake()->optional()->safeEmail(),
            'notas'     => null,
            'saldo_cache_usd' => 0,
            'activo'    => true,
        ];
    }
}
