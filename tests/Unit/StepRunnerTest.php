<?php

declare(strict_types=1);

use App\Enums\AutomationStepKind;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\DeviceToken;
use App\Models\PushSetting;
use App\Models\Workspace;
use App\Services\Automation\StepRunner;
use App\Services\Push\ExpoDriver;
use App\Services\Push\PushDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders and sends push notification through driver', function (): void {
    $workspace = Workspace::factory()->create();
    $contact = Contact::factory()->for($workspace)->create(['first_name' => 'Jane']);
    DeviceToken::factory()->for($contact)->create(['driver' => 'expo']);
    $setting = PushSetting::create([
        'workspace_id' => $workspace->id,
        'driver' => 'expo',
        'api_key_encrypted' => encrypt('key'),
        'project_id' => 'proj',
    ]);

    $fake = new class($setting) implements PushDriver {
        public array $sent = [];
        public function __construct(public PushSetting $setting) {}
        public function send(Contact $contact, array $message): void
        {
            $this->sent[] = ['contact' => $contact, 'message' => $message];
        }
    };

    app()->bind(ExpoDriver::class, fn ($app, $params) => $fake);

    $automation = Automation::factory()->for($workspace)->create();
    $step = AutomationStep::factory()->for($automation)->create([
        'kind' => AutomationStepKind::SendPushNotification,
        'config' => [
            'template_title' => 'Hello {{ $contact->first_name }}',
            'template_body' => 'Body',
            'data' => ['foo' => 'bar'],
        ],
    ]);

    $runner = app(StepRunner::class);
    $runner->run($step, $contact);

    expect($fake->sent)->toHaveCount(1)
        ->and($fake->sent[0]['message'])
        ->toMatchArray([
            'title' => 'Hello Jane',
            'body' => 'Body',
            'data' => ['foo' => 'bar'],
        ]);
});

