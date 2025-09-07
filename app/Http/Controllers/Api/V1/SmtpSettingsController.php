<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Mail\TestSmtpMail;
use App\Models\SmtpSetting;
use App\Services\Mail\MailerResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SmtpSettingsController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'integer'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'encryption' => ['nullable', 'string'],
            'from_name' => ['nullable', 'string'],
            'from_email' => ['required', 'email'],
            'reply_to' => ['nullable', 'email'],
        ]);

        $setting = SmtpSetting::updateOrCreate(
            ['workspace_id' => currentWorkspace()->id],
            [
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'],
                'password_encrypted' => encrypt($data['password']),
                'encryption' => $data['encryption'] ?? null,
                'from_name' => $data['from_name'] ?? null,
                'from_email' => $data['from_email'],
                'reply_to' => $data['reply_to'] ?? null,
                'is_active' => true,
            ]
        );

        return response()->json(['data' => $this->transform($setting)]);
    }

    public function show(): Response
    {
        $setting = SmtpSetting::where('workspace_id', currentWorkspace()->id)->first();

        if ($setting === null) {
            return response()->json([
                'errors' => [
                    ['status' => '404', 'title' => 'SMTP setting not found'],
                ],
            ], 404);
        }

        return response()->json(['data' => $this->transform($setting)]);
    }

    public function test(Request $request, MailerResolver $resolver): Response
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
        ]);

        $mailer = $resolver->for(currentWorkspace());
        $mailer->to($data['to'])->send(new TestSmtpMail);

        return response()->json(['data' => ['sent' => true]]);
    }

    private function transform(SmtpSetting $setting): array
    {
        return [
            'id' => $setting->id,
            'host' => $setting->host,
            'port' => $setting->port,
            'username' => $setting->username,
            'password' => '********',
            'encryption' => $setting->encryption,
            'from_name' => $setting->from_name,
            'from_email' => $setting->from_email,
            'reply_to' => $setting->reply_to,
            'is_active' => $setting->is_active,
        ];
    }
}
