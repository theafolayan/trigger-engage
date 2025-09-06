<?php

declare(strict_types=1);

use App\Jobs\ProcessEvent;
use App\Models\Contact;
use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

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

it('stores event, links contact, and dispatches job', function (): void {
    Bus::fake();

    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();

    $response = postJson('/api/events', [
        'name' => 'user.signed_up',
        'contact_email' => 'jane@example.com',
        'payload' => ['plan' => 'pro'],
        'auto_create_contact' => true,
    ], authHeaders($user, $workspace));

    $response->assertCreated();

    $contact = Contact::where('workspace_id', $workspace->id)->first();
    expect($contact)->not->toBeNull();

    $event = Event::first();

    expect($event->contact_id)->toBe($contact->id)
        ->and($event->name)->toBe('user.signed_up')
        ->and($event->payload)->toBe(['plan' => 'pro']);

    Bus::assertDispatched(ProcessEvent::class, function (ProcessEvent $job) use ($event) {
        return $job->event->is($event);
    });
});
