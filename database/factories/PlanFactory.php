<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'features' => [],
            'email_quota' => 1000,
            'event_quota' => 1000,
            'contact_quota' => 1000,
        ];
    }
}
