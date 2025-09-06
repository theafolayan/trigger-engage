<?php

declare(strict_types=1);

use App\Mail\TemplateTestMail;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Template;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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

it('crud works', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();

    $response = postJson('/api/v1/templates', [
        'name' => 'Welcome',
        'subject' => 'Hello {{ $contact->first_name }}',
        'html' => '<p>Hi {{ $contact->first_name }}</p>',
        'text' => 'Hi {{ $contact->first_name }}',
    ], authHeaders($user, $workspace));

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Welcome');

    $id = $response->json('data.id');

    $response = putJson("/api/v1/templates/{$id}", [
        'name' => 'Updated',
    ], authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated');

    $response = deleteJson("/api/v1/templates/{$id}", [], authHeaders($user, $workspace));
    $response->assertNoContent();

    expect(Template::count())->toBe(0);
});

it('preview substitutes variables', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    $contact = Contact::factory()->for($workspace)->create(['first_name' => 'Jane']);
    $event = Event::factory()->for($workspace)->for($contact)->create([
        'payload' => ['plan' => 'Pro'],
    ]);
    $template = Template::factory()->for($workspace)->create([
        'subject' => 'Hello {{ $contact->first_name }} about {{ $event->payload["plan"] }}',
        'html' => '<p>Dear {{ $contact->first_name }}</p>',
        'text' => 'Dear {{ $contact->first_name }}',
    ]);

    $response = postJson("/api/v1/templates/{$template->id}/preview", [
        'contact_id' => $contact->id,
        'event_id' => $event->id,
    ], authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.subject', 'Hello Jane about Pro')
        ->assertJsonPath('data.html', '<p>Dear Jane</p>')
        ->assertJsonPath('data.text', 'Dear Jane');
});

it('test send enqueues mail', function (): void {
    Mail::fake();

    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    $contact = Contact::factory()->for($workspace)->create(['first_name' => 'Jane']);
    $event = Event::factory()->for($workspace)->for($contact)->create();
    $template = Template::factory()->for($workspace)->create([
        'subject' => 'Hi {{ $contact->first_name }}',
        'html' => '<p>Hello {{ $contact->first_name }}</p>',
        'text' => 'Hello {{ $contact->first_name }}',
    ]);

    $response = postJson("/api/v1/templates/{$template->id}/test", [
        'to' => 'test@example.com',
        'contact_id' => $contact->id,
        'event_id' => $event->id,
    ], authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.sent', true);

    Mail::assertQueued(TemplateTestMail::class, function (TemplateTestMail $mail) {
        return $mail->hasTo('test@example.com')
            && str_contains($mail->htmlContent, 'Hello Jane')
            && $mail->subjectText === 'Hi Jane';
    });
});
