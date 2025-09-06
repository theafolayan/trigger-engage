<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Filament\Auth\Pages\Login as FilamentLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects guests to login', function (): void {
    get('/admin')->assertRedirect('/admin/login');
});

it('allows admin to login and access panel', function (): void {
    $workspace = Workspace::factory()->create();
    $admin = User::factory()->for($workspace)->admin()->create();

    Livewire::test(FilamentLogin::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    get('/admin')->assertOk();
});

it('blocks non-admin users', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();

    Livewire::test(FilamentLogin::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    get('/admin')->assertStatus(403);
});

