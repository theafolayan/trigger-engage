<?php

declare(strict_types=1);

use App\Filament\Resources\SmtpSettingResource\Pages\CreateSmtpSetting;
use App\Filament\Resources\PushSettingResource\Pages\CreatePushSetting;
use App\Models\Account;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows admins to create smtp settings', function (): void {
    $account = Account::factory()->create();
    $workspace = Workspace::factory()->for($account)->create(['slug' => 'one']);
    $admin = User::factory()->for($account)->for($workspace)->admin()->create();

    actingAs($admin);
    app()->instance('currentWorkspace', $workspace);

    Livewire::test(CreateSmtpSetting::class)
        ->fillForm([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'jane',
            'password' => 'secret',
            'from_email' => 'jane@example.com',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('smtp_settings', [
        'workspace_id' => $workspace->id,
        'host' => 'smtp.example.com',
        'from_email' => 'jane@example.com',
    ]);
});

it('allows admins to create push settings', function (): void {
    $account = Account::factory()->create();
    $workspace = Workspace::factory()->for($account)->create(['slug' => 'one']);
    $admin = User::factory()->for($account)->for($workspace)->admin()->create();

    actingAs($admin);
    app()->instance('currentWorkspace', $workspace);

    Livewire::test(CreatePushSetting::class)
        ->fillForm([
            'driver' => 'one_signal',
            'api_key' => 'secret',
            'app_id' => 'app',
            'project_id' => 'proj',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('push_settings', [
        'workspace_id' => $workspace->id,
        'driver' => 'one_signal',
        'app_id' => 'app',
    ]);
});

