<?php

declare(strict_types=1);

use App\Enums\ContactStatus;
use App\Enums\DeliveryStatus;
use App\Enums\SuppressionReason;
use App\Mail\TemplateTestMail;
use App\Models\Automation;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\Template;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Mail\MailerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

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

it('suppresses contact on postmark bounce webhook', function (): void {
    $workspace = Workspace::factory()->create();
    $contact = Contact::factory()->for($workspace)->create([
        'email' => 'john@example.com',
    ]);
    $template = Template::factory()->for($workspace)->create();
    $automation = Automation::factory()->for($workspace)->create();
    $delivery = Delivery::factory()->for($workspace)->for($contact)->for($template)->for($automation)->create([
        'provider_message_id' => 'abc-123',
        'status' => DeliveryStatus::Sent,
    ]);

    $payload = json_decode(file_get_contents(base_path('tests/fixtures/postmark-bounce.json')), true);
    $payload['MessageID'] = 'abc-123';
    $payload['Email'] = 'john@example.com';

    $response = postJson('/webhooks/postmark', $payload);
    $response->assertOk();

    expect($contact->fresh()->status)->toBe(ContactStatus::Bounced)
        ->and($delivery->fresh()->status)->toBe(DeliveryStatus::Bounced)
        ->and($workspace->suppressions()->where('email', 'john@example.com')->exists())->toBeTrue();
});

it('skips sending to suppressed contact', function (): void {
    Mail::fake();

    $workspace = Workspace::factory()->create();
    $contact = Contact::factory()->for($workspace)->create([
        'email' => 'john@example.com',
    ]);
    $workspace->suppressions()->create([
        'email' => 'john@example.com',
        'reason' => SuppressionReason::Bounce,
        'source' => [],
    ]);

    $resolver = app(MailerResolver::class);
    $mailer = $resolver->for($workspace, $contact);
    $mailer->to($contact->email)->queue(new TemplateTestMail('Test', '<p>Hi</p>', 'Hi'));

    Mail::assertNothingQueued();
});
