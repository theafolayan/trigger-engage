<?php

declare(strict_types=1);

use App\Enums\AutomationStepKind;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\DeviceToken;
use App\Models\PushSetting;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Push\ExpoDriver;
use App\Services\Push\PushDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

if (! function_exists('authHeaders')) {
    function authHeaders(User $user, Workspace $workspace): array
    {
        $token = $user->createToken('api')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Workspace' => $workspace->slug,
        ];
    }
}

it('sends push notification when event ingested', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
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
    AutomationStep::factory()->for($automation)->create([
        'kind' => AutomationStepKind::SendPushNotification,
        'config' => [
            'template_title' => 'Hi {{ $contact->first_name }}',
            'template_body' => 'Hello',
        ],
    ]);

    postJson('/api/events', [
        'name' => 'signup',
        'contact_email' => $contact->email,
    ], authHeaders($user, $workspace))->assertCreated();

    expect($fake->sent)->toHaveCount(1)
        ->and($fake->sent[0]['message']['title'])->toBe('Hi Jane');
});

