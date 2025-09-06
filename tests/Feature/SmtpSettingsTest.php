<?php

declare(strict_types=1);

use App\Mail\TestSmtpMail;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function authHeaders(User $user, Workspace $workspace): array
{
    $token = $user->createToken('api')->plainTextToken;

    return [
        'Authorization' => "Bearer {$token}",
        'X-Workspace' => $workspace->slug,
    ];
}

it('stores settings encrypting password', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();

    $response = postJson('/api/smtp-settings', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'jane',
        'password' => 'secret',
        'encryption' => 'tls',
        'from_name' => 'Jane',
        'from_email' => 'jane@example.com',
        'reply_to' => 'reply@example.com',
    ], authHeaders($user, $workspace));

    $response->assertOk();

    $setting = SmtpSetting::first();
    expect($setting->password_encrypted)->not->toBe('secret')
        ->and(decrypt($setting->password_encrypted))->toBe('secret');
});

it('shows masked password on fetch', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    SmtpSetting::factory()->for($workspace)->create([
        'password_encrypted' => encrypt('super-secret'),
    ]);

    $response = getJson('/api/smtp-settings', authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.password', '********');
});

it('can test smtp settings sending mail', function (): void {
    Mail::fake();

    $workspace = Workspace::factory()->create();
    $user = User::factory()->for($workspace)->create();
    SmtpSetting::factory()->for($workspace)->create([
        'password_encrypted' => encrypt('secret'),
    ]);

    $response = postJson('/api/smtp-settings/test', [
        'to' => 'test@example.com',
    ], authHeaders($user, $workspace));

    $response->assertOk()
        ->assertJsonPath('data.sent', true);

    Mail::assertSent(TestSmtpMail::class, function (TestSmtpMail $mail) {
        return $mail->hasTo('test@example.com');
    });
});
