<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PushSetting>
 */
class PushSettingFactory extends Factory
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
            'driver' => 'expo',
            'api_key_encrypted' => encrypt('key'),
            'app_id' => null,
            'project_id' => 'project',
            'meta' => [],
            'is_active' => true,
        ];
    }
}

