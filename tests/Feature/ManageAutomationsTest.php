<?php

declare(strict_types=1);

use App\Enums\AutomationStepKind;
use App\Filament\Resources\AutomationResource\Pages\CreateAutomation;
use App\Models\Account;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Template;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows admins to create automations', function (): void {
    $account = Account::factory()->create();
    $workspace = Workspace::factory()->for($account)->create(['slug' => 'demo']);
    $admin = User::factory()->for($account)->for($workspace)->admin()->create();
    $template = Template::factory()->for($workspace)->create();

    actingAs($admin);
    app()->instance('currentWorkspace', $workspace);

    Livewire::test(CreateAutomation::class)
        ->fillForm([
            'name' => 'Welcome Flow',
            'trigger_event' => 'signup',
            'is_active' => true,
            'timezone' => 'UTC',
            'conditions' => [
                [
                    'path' => 'event.name',
                    'op' => '==',
                    'value' => 'signup',
                ],
                [
                    'path' => 'contact.tags',
                    'op' => 'in',
                    'value' => '["vip","beta"]',
                ],
            ],
            'steps' => [
                [
                    'uid' => 'start',
                    'kind' => AutomationStepKind::SendEmail->value,
                    'config' => json_encode(['template_id' => $template->id], JSON_UNESCAPED_SLASHES),
                    'next_step_uid' => 'delay',
                    'alt_next_step_uid' => null,
                ],
                [
                    'uid' => 'delay',
                    'kind' => AutomationStepKind::Delay->value,
                    'config' => json_encode(['minutes' => 10], JSON_UNESCAPED_SLASHES),
                    'next_step_uid' => null,
                    'alt_next_step_uid' => null,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $automation = Automation::where('workspace_id', $workspace->id)->firstOrFail();

    expect($automation->name)->toBe('Welcome Flow')
        ->and($automation->trigger_event)->toBe('signup')
        ->and($automation->conditions)->toBe([
            ['path' => 'event.name', 'op' => '==', 'value' => 'signup'],
            ['path' => 'contact.tags', 'op' => 'in', 'value' => ['vip', 'beta']],
        ]);

    $start = AutomationStep::where('automation_id', $automation->id)->where('uid', 'start')->firstOrFail();
    $delay = AutomationStep::where('automation_id', $automation->id)->where('uid', 'delay')->firstOrFail();

    expect($start->kind)->toBe(AutomationStepKind::SendEmail)
        ->and($start->config)->toBe(['template_id' => $template->id])
        ->and($start->next_step_uid)->toBe('delay');

    expect($delay->kind)->toBe(AutomationStepKind::Delay)
        ->and($delay->config)->toBe(['minutes' => 10])
        ->and($delay->next_step_uid)->toBeNull();
});
