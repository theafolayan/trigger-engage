<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\Contact;
use App\Models\PushSetting;
use Illuminate\Support\Facades\Http;

class OneSignalDriver implements PushDriver
{
    public function __construct(private PushSetting $setting) {}

    public function send(Contact $contact, array $message): void
    {
        $tokens = $contact->deviceTokens()->where('driver', 'one_signal')->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        Http::withHeaders([
            'Authorization' => 'Bearer ' . decrypt($this->setting->api_key_encrypted),
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $this->setting->app_id,
            'include_player_ids' => $tokens,
            'headings' => ['en' => $message['title']],
            'contents' => ['en' => $message['body']],
            'data' => $message['data'] ?? [],
        ])->throw();
    }
}

