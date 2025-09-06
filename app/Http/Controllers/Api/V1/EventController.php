<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Jobs\ProcessEvent;
use App\Models\Contact;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function ingest(Request $request): Response
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
            'contact_email' => ['nullable', 'email'],
            'occurred_at' => ['nullable', 'date'],
            'auto_create_contact' => ['nullable', 'boolean'],
        ]);

        $contact = null;

        if (isset($data['contact_email'])) {
            $contact = Contact::where('workspace_id', currentWorkspace()->id)
                ->where('email', $data['contact_email'])
                ->first();

            if ($contact === null && ($data['auto_create_contact'] ?? false)) {
                $contact = Contact::create([
                    'workspace_id' => currentWorkspace()->id,
                    'email' => $data['contact_email'],
                ]);
            }
        }

        $event = Event::create([
            'workspace_id' => currentWorkspace()->id,
            'name' => $data['name'],
            'contact_id' => $contact?->id,
            'payload' => $data['payload'] ?? [],
            'occurred_at' => isset($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])
                : now(),
        ]);

        ProcessEvent::dispatch($event);

        return response()->json([
            'data' => [
                'id' => $event->id,
            ],
        ], 201);
    }
}
