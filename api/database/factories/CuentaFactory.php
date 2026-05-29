<?php

namespace Database\Factories;

use App\Models\Banco;
use App\Models\Moneda;
use App\Models\Titular;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titular_id'    => Titular::factory(),
            'banco_id'      => null,
            'moneda_id'     => Moneda::factory(),
            'alias'         => fake()->unique()->word(),
            'tipo'          => fake()->randomElement(['banco', 'plataforma', 'cash', 'wallet']),
            'numero_cuenta' => fake()->optional()->numerify('##########'),
            'saldo_cache'   => 0,
            'activa'        => true,
            'notas'         => null,
        ];
    }

    public function banco(): static
    {
        return $this->state(['tipo' => 'banco', 'banco_id' => Banco::factory()]);
    }

    public function inactiva(): static
    {
        return $this->state(['activa' => false]);
    }
}
