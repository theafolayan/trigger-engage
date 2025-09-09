<?php

declare(strict_types=1);

use App\Filament\Resources\WorkspaceResource\Pages\CreateWorkspace;
use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows admins to create workspaces', function (): void {
    $account = Account::factory()->create();
    $workspace = Workspace::factory()->for($account)->create(['slug' => 'one']);
    $admin = User::factory()->for($account)->for($workspace)->admin()->create();

    actingAs($admin);

    Livewire::test(CreateWorkspace::class)
        ->fillForm([
            'name' => 'New Workspace',
            'slug' => 'new-workspace',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('workspaces', [
        'account_id' => $account->id,
        'name' => 'New Workspace',
        'slug' => 'new-workspace',
    ]);
});

it('lists only workspaces for the users account', function (): void {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $workspaceOne = Workspace::factory()->for($account)->create(['name' => 'One', 'slug' => 'one']);
    Workspace::factory()->for($otherAccount)->create(['name' => 'Two', 'slug' => 'two']);
    $admin = User::factory()->for($account)->for($workspaceOne)->admin()->create();

    actingAs($admin);

    Livewire::test(\App\Filament\Resources\WorkspaceResource\Pages\ListWorkspaces::class)
        ->assertSee('One')
        ->assertDontSee('Two');
});

