<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

if (!function_exists('authHeaders')) {
    function authHeaders(User $user, Workspace $workspace): array
    {
        $token = $user->createToken('api')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Workspace' => $workspace->slug,
        ];
    }
}

it('upsert creates contact', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();

    $response = postJson('/api/v1/contacts', [
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
    ], authHeaders($user, $workspace));

    $response->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com');

    expect(Contact::where('workspace_id', $workspace->id)->count())->toBe(1);
});

it('upsert updates existing contact on second call', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();

    postJson('/api/v1/contacts', [
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
    ], authHeaders($user, $workspace));

    $response = postJson('/api/v1/contacts', [
        'email' => 'jane@example.com',
        'first_name' => 'Janet',
    ], authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.first_name', 'Janet');

    expect(Contact::where('workspace_id', $workspace->id)->count())->toBe(1)
        ->and(Contact::first()->first_name)->toBe('Janet');
});

it('bulk import returns counts', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    Contact::factory()->for($workspace)->create(['email' => 'jane@example.com']);

    $payload = [
        ['email' => 'jane@example.com', 'first_name' => 'Janet'],
        ['email' => 'john@example.com', 'first_name' => 'John'],
    ];

    $response = postJson('/api/v1/contacts/import', $payload, authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.created', 1)
        ->assertJsonPath('data.updated', 1)
        ->assertJsonPath('data.errors', []);

    expect(Contact::where('workspace_id', $workspace->id)->count())->toBe(2)
        ->and(Contact::where('email', 'jane@example.com')->first()->first_name)->toBe('Janet');
});
