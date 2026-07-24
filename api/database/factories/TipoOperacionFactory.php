<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TipoOperacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo'          => fake()->unique()->lexify('tipo_???'),
            'nombre'          => fake()->words(2, true),
            'afecta_cliente'  => false,
            'afecta_fifo'     => false,
            'genera_ganancia' => false,
            'activo'          => true,
        ];
    }

    public function ventaUsd(): static
    {
        return $this->state(['codigo' => 'venta_usd', 'nombre' => 'Venta de USD', 'afecta_cliente' => true, 'afecta_fifo' => true, 'genera_ganancia' => false]);
    }

    public function compraUsd(): static
    {
        return $this->state(['codigo' => 'compra_usd', 'nombre' => 'Compra de USD', 'afecta_cliente' => true, 'afecta_fifo' => true, 'genera_ganancia' => true]);
    }

    public function cambio(): static
    {
        return $this->state(['codigo' => 'cambio', 'nombre' => 'Cambio de moneda', 'afecta_fifo' => true, 'genera_ganancia' => false]);
    }

    public function gasto(): static
    {
        return $this->state(['codigo' => 'gasto', 'nombre' => 'Gasto operativo', 'genera_ganancia' => false]);
    }

    public function comision(): static
    {
        return $this->state(['codigo' => 'comision', 'nombre' => 'Comisión', 'afecta_cliente' => true, 'genera_ganancia' => true]);
    }

    public function traslado(): static
    {
        return $this->state(['codigo' => 'traslado', 'nombre' => 'Traslado interno', 'afecta_fifo' => false, 'genera_ganancia' => false]);
    }

    public function ajuste(): static
    {
        return $this->state(['codigo' => 'ajuste', 'nombre' => 'Ajuste contable', 'genera_ganancia' => false]);
    }

    public function ajusteApertura(): static
    {
        return $this->state(['codigo' => 'ajuste_apertura', 'nombre' => 'Ajuste de apertura', 'afecta_fifo' => true, 'genera_ganancia' => false]);
    }
}
