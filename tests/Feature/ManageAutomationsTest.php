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
use Illuminate\Support\Str;
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

    $startLabel = 'Send welcome email';
    $startUid = Str::slug($startLabel);
    $delayLabel = 'Delay before follow-up';
    $delayUid = Str::slug($delayLabel);

    Livewire::test(CreateAutomation::class)
        ->fillForm([
            'name' => 'Welcome Flow',
            'trigger_event' => 'signup',
            'is_active' => true,
            'timezone' => 'UTC',
            'conditions' => [
                [
                    'attribute' => 'event.name',
                    'custom_path' => null,
                    'op' => '==',
                    'type' => 'string',
                    'value_text' => 'signup',
                    'value_number' => null,
                    'value_boolean' => false,
                    'value_array' => [],
                ],
                [
                    'attribute' => 'contact.tags',
                    'custom_path' => null,
                    'op' => 'in',
                    'type' => 'array',
                    'value_text' => '',
                    'value_number' => null,
                    'value_boolean' => false,
                    'value_array' => ['vip', 'beta'],
                ],
            ],
            'steps' => [
                [
                    'label' => $startLabel,
                    'uid' => $startUid,
                    'kind' => AutomationStepKind::SendEmail->value,
                    'config' => ['template_id' => $template->id],
                    'next_step_uid' => $delayUid,
                    'alt_next_step_uid' => null,
                ],
                [
                    'label' => $delayLabel,
                    'uid' => $delayUid,
                    'kind' => AutomationStepKind::Delay->value,
                    'config' => ['minutes' => 10],
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

    $start = AutomationStep::where('automation_id', $automation->id)->where('uid', $startUid)->firstOrFail();
    $delay = AutomationStep::where('automation_id', $automation->id)->where('uid', $delayUid)->firstOrFail();

    expect($start->kind)->toBe(AutomationStepKind::SendEmail)
        ->and($start->config)->toBe(['template_id' => $template->id])
        ->and($start->next_step_uid)->toBe($delayUid);

    expect($delay->kind)->toBe(AutomationStepKind::Delay)
        ->and($delay->config)->toBe(['minutes' => 10])
        ->and($delay->next_step_uid)->toBeNull();
});
