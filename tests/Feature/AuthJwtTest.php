<?php

declare(strict_types=1);

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('login returns access and refresh tokens', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => ['access_token', 'refresh_token'],
    ]);

    expect(RefreshToken::count())->toBe(1);
});

it('refresh rotates the refresh token', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $login = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret',
    ])->json('data');

    $oldRefresh = $login['refresh_token'];

    $first = postJson('/api/auth/refresh', ['refresh_token' => $oldRefresh])->json('data');

    expect($first['refresh_token'])->not->toBe($oldRefresh);

    postJson('/api/auth/refresh', ['refresh_token' => $oldRefresh])->assertUnauthorized();
});

it('logout revokes the refresh token', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $login = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret',
    ])->json('data');

    postJson('/api/auth/logout', ['refresh_token' => $login['refresh_token']])->assertNoContent();

    postJson('/api/auth/refresh', ['refresh_token' => $login['refresh_token']])->assertUnauthorized();
});

it('adds CORS headers for allowed origin', function (): void {
    config(['cors.allowed_origins' => ['https://allowed.test']]);

    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret',
    ], ['Origin' => 'https://allowed.test']);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('https://allowed.test');
});

it('blocks disallowed origins', function (): void {
    config(['cors.allowed_origins' => ['https://allowed.test']]);

    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret',
    ], ['Origin' => 'https://blocked.test']);

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://blocked.test');
});
