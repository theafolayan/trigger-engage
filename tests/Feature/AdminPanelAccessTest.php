<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('blocks non-admin users from panel', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create([
        'is_admin' => false,
    ]);

    actingAs($user);

    $response = get('/admin');

    $response->assertStatus(403);
});

it('allows admin users into panel', function (): void {
    $workspace = Workspace::factory()->create();
    $admin = User::factory()->for($workspace)->create([
        'is_admin' => true,
    ]);

    actingAs($admin);

    $response = get('/admin');

    $response->assertOk();
});
