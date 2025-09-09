<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('defaults workspace and allows switching', function (): void {
    $account = Account::factory()->create();
    $workspaceOne = Workspace::factory()->for($account)->create(['slug' => 'one']);
    $workspaceTwo = Workspace::factory()->for($account)->create(['slug' => 'two']);
    $admin = User::factory()->for($account)->for($workspaceOne)->admin()->create();

    actingAs($admin);

    get('/admin')->assertOk()->assertSessionHas('workspace', 'one');

    session(['workspace' => 'two']);

    get('/admin')->assertOk()->assertSessionHas('workspace', 'two');
});
