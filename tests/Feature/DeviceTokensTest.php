<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\deleteJson;
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

it('removes device token from contact', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    $contact = Contact::factory()->for($workspace)->create();

    postJson("/api/v1/contacts/{$contact->id}/device-tokens", [
        'token' => 'abc',
        'platform' => 'ios',
        'driver' => 'expo',
    ], authHeaders($user, $workspace))->assertCreated();

    deleteJson("/api/v1/contacts/{$contact->id}/device-tokens/abc", [], authHeaders($user, $workspace))
        ->assertNoContent();

    expect($contact->deviceTokens()->count())->toBe(0);
});

it('returns 404 for missing device token', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    $contact = Contact::factory()->for($workspace)->create();

    deleteJson("/api/v1/contacts/{$contact->id}/device-tokens/missing", [], authHeaders($user, $workspace))
        ->assertNotFound();
});

