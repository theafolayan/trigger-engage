<?php

namespace Database\Factories;

use App\Enums\AutomationStepKind;
use App\Models\Automation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\AutomationStep>
 */
class AutomationStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'automation_id' => Automation::factory(),
            'uid' => Str::uuid()->toString(),
            'kind' => AutomationStepKind::Delay,
            'config' => [],
            'next_step_uid' => null,
            'alt_next_step_uid' => null,
        ];
    }
}
