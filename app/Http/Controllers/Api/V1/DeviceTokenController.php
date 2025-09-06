<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\Contact;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenController extends Controller
{
    public function store(Contact $contact, Request $request): Response
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'string'],
            'driver' => ['required', 'string'],
        ]);

        $contact->deviceTokens()->updateOrCreate(
            ['token' => $data['token']],
            ['platform' => $data['platform'], 'driver' => $data['driver']]
        );

        return response()->json(['data' => ['token' => $data['token']]], 201);
    }
}

