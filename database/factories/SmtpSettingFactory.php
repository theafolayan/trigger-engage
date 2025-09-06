<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SmtpSetting>
 */
class SmtpSettingFactory extends Factory
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
            'host' => fake()->domainName(),
            'port' => 587,
            'username' => fake()->userName(),
            'password_encrypted' => fake()->password(),
            'encryption' => 'tls',
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'reply_to' => fake()->safeEmail(),
            'meta' => [],
            'is_active' => true,
        ];
    }
}
