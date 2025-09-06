<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use App\Models\Contact;
use App\Models\Delivery;
use App\Enums\DeliveryStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

if (! function_exists('authHeaders')) {
    function authHeaders(User $user, Workspace $workspace): array
    {
        $token = $user->createToken('api')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Workspace' => $workspace->slug,
        ];
    }
}

it('blocks non-admin users', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create([
        'is_admin' => false,
    ]);

    $response = getJson('/api/admin/stats', authHeaders($user, $workspace));

    $response->assertStatus(403);
});

it('returns stats for admin', function (): void {
    $workspace = Workspace::factory()->create();
    $admin = User::factory()->for($workspace)->create([
        'is_admin' => true,
    ]);

    Contact::factory()->for($workspace)->count(3)->create();

    Delivery::factory()->for($workspace)->state([
        'status' => DeliveryStatus::Sent,
        'sent_at' => now(),
    ])->count(2)->create();

    $response = getJson('/api/admin/stats', authHeaders($admin, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.contacts.total', 3)
        ->assertJsonPath('data.deliveries.sent', 2);
});
