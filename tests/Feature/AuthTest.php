<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('can request token with email/password', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create([
        'password' => bcrypt('secret'),
    ]);

    $response = postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'secret',
    ], ['X-Workspace' => $workspace->slug]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token']]);
});
