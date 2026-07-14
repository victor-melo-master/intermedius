<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\Moneda;
use App\Models\Operacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'operacion_id'     => Operacion::factory(),
            'cuenta_origen_id'  => Cuenta::factory(),
            'cuenta_destino_id' => Cuenta::factory(),
            'moneda_id'         => Moneda::factory(),
            'monto'             => fake()->randomFloat(4, 1, 10000),
            'estado'            => 'pendiente',
            'orden'             => fake()->numberBetween(1, 10),
        ];
    }
}
