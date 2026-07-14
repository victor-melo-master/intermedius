<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LoginAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->email(),
            'ip_address' => fake()->ipv4(),
            'attempted_at' => now(),
            'successful' => false,
        ];
    }
}
