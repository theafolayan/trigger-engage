<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\DeliveryStatus;
use App\Filament\Widgets\StatsOverview;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Filament\Auth\Pages\Login as FilamentLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

it('redirects guests to login', function (): void {
    get('/admin')->assertRedirect('/admin/login');
});

it('allows admin to login and access panel', function (): void {
    $workspace = Workspace::factory()->create();
    $admin = User::factory()->for($workspace)->admin()->create();

    $contact = Contact::factory()->for($workspace)->create();
    Delivery::factory()->for($workspace)->for($contact)->create([
        'status' => DeliveryStatus::Sent,
        'sent_at' => now(),
    ]);
    Event::factory()->for($workspace)->for($contact)->create();

    Livewire::test(FilamentLogin::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    actingAs($admin);

    Livewire::test(StatsOverview::class)
        ->assertSee('Contacts')
        ->assertSee('1')
        ->assertSee('Deliveries')
        ->assertSee('1')
        ->assertSee('Events')
        ->assertSee('1');
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
