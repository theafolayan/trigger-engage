<?php

namespace Database\Factories;

use App\Enums\SuppressionReason;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Suppression>
 */
class SuppressionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => fake()->safeEmail(),
            'reason' => SuppressionReason::Manual,
            'source' => [],
        ];
    }
}
