<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\Event;
use App\Services\TrackingToken;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TrackingController extends Controller
{
    public function __construct(private TrackingToken $tokens)
    {
    }

    public function open(string $token): SymfonyResponse
    {
        $data = $this->tokens->verify($token);
        $this->logEvent($data, 'open');

        $gif = base64_decode('R0lGODlhAQABAPAAAP///wAAACwAAAAAAQABAAACAkQBADs=');

        return response($gif, SymfonyResponse::HTTP_OK, [
            'Content-Type' => 'image/gif',
        ]);
    }

    public function click(string $token): RedirectResponse
    {
        $data = $this->tokens->verify($token);
        $url = $data['url'] ?? '/';

        $this->logEvent($data, 'click', ['url' => $url]);

        return redirect()->away($url);
    }

    public function unsubscribe(string $token): SymfonyResponse
    {
        $data = $this->tokens->verify($token);

        if ($data !== null && isset($data['contact_id'])) {
            $contact = Contact::find($data['contact_id']);

            if ($contact !== null) {
                $contact->status = ContactStatus::Unsubscribed;
                $contact->save();

                Event::create([
                    'workspace_id' => $contact->workspace_id,
                    'contact_id' => $contact->id,
                    'name' => 'unsubscribe',
                    'payload' => [],
                    'occurred_at' => now(),
                ]);
            }
        }

        return response()->json(['data' => ['unsubscribed' => true]]);
    }

    private function logEvent(?array $data, string $name, array $payload = []): void
    {
        if ($data === null || !isset($data['contact_id'])) {
            return;
        }

        $contact = Contact::find($data['contact_id']);

        if ($contact === null) {
            return;
        }

        Event::create([
            'workspace_id' => $contact->workspace_id,
            'contact_id' => $contact->id,
            'name' => $name,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
