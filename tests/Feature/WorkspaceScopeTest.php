<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('requires token and workspace header', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    $token = $user->createToken('api')->plainTextToken;

    getJson('/api/v1/ping')->assertUnauthorized();

    getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$token])
        ->assertStatus(400);

    $this->flushHeaders();
    auth()->forgetGuards();

    getJson('/api/v1/ping', ['X-Workspace' => $workspace->slug])
        ->assertUnauthorized();

    getJson('/api/v1/ping', [
        'Authorization' => 'Bearer '.$token,
        'X-Workspace' => $workspace->slug,
    ])->assertOk();
});
