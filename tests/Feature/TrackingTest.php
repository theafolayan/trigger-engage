<?php

declare(strict_types=1);

use App\Mail\TestSmtpMail;
use App\Models\Contact;
use App\Services\TrackingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('logs open events', function (): void {
    $contact = Contact::factory()->create();
    $token = app(TrackingToken::class)->sign(['contact_id' => $contact->id]);

    $response = get("/t/o/{$token}");

    $response->assertOk()->assertHeader('Content-Type', 'image/gif');
    expect($contact->events()->where('name', 'open')->count())->toBe(1);
});

it('logs click events and redirects', function (): void {
    $contact = Contact::factory()->create();
    $url = 'https://example.com';
    $token = app(TrackingToken::class)->sign(['contact_id' => $contact->id, 'url' => $url]);

    $response = get("/t/c/{$token}");

    $response->assertRedirect($url);
    expect($contact->events()->where('name', 'click')->where('payload->url', $url)->count())->toBe(1);
});

it('unsubscribes contact', function (): void {
    $contact = Contact::factory()->create();
    $token = app(TrackingToken::class)->sign(['contact_id' => $contact->id]);

    $response = get("/t/u/{$token}");

    $response->assertOk();
    expect($contact->refresh()->status->value)->toBe('unsubscribed');
});

it('injects unsubscribe header into mail', function (): void {
    config(['mail.default' => 'array']);
    $contact = Contact::factory()->create();

    Mail::to($contact->email)->send(new TestSmtpMail());

    $messages = Mail::getSymfonyTransport()->messages();
    $headers = $messages[0]->getOriginalMessage()->getHeaders();

    $token = app(TrackingToken::class)->sign(['contact_id' => $contact->id]);
    $expected = '<'.url("/t/u/{$token}").'>';

    expect($headers->get('List-Unsubscribe')->getBody())->toBe($expected);
});
