<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\Contact;
use App\Models\PushSetting;
use Illuminate\Support\Facades\Http;

class ExpoDriver implements PushDriver
{
    public function __construct(private PushSetting $setting) {}

    public function send(Contact $contact, array $message): void
    {
        $tokens = $contact->deviceTokens()->where('driver', 'expo')->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        Http::withToken(decrypt($this->setting->api_key_encrypted))
            ->post('https://exp.host/--/api/v2/push/send', [
                'to' => $tokens,
                'title' => $message['title'],
                'body' => $message['body'],
                'data' => $message['data'] ?? [],
                'projectId' => $this->setting->project_id,
            ])->throw();
    }
}

