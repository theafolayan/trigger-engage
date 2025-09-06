<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Automation;
use App\Models\Contact;
use App\Models\Template;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
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
            'contact_id' => Contact::factory(),
            'template_id' => Template::factory(),
            'automation_id' => Automation::factory(),
            'step_uid' => Str::uuid()->toString(),
            'status' => DeliveryStatus::Pending,
            'provider_message_id' => Str::uuid()->toString(),
            'scheduled_at' => now(),
            'sent_at' => null,
            'error' => null,
        ];
    }
}
