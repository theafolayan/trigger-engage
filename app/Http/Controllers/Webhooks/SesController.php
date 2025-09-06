<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\ContactStatus;
use App\Enums\DeliveryStatus;
use App\Enums\SuppressionReason;
use App\Models\Delivery;
use App\Models\Suppression;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SesController
{
    public function __invoke(Request $request): Response
    {
        $data = $request->json()->all();

        $type = $data['eventType'] ?? $data['notificationType'] ?? null;
        $messageId = $data['mail']['messageId'] ?? null;
        $recipients = [];
        if (isset($data['bounce']['bouncedRecipients'])) {
            $recipients = $data['bounce']['bouncedRecipients'];
        } elseif (isset($data['complaint']['complainedRecipients'])) {
            $recipients = $data['complaint']['complainedRecipients'];
        }
        $email = $recipients[0]['emailAddress'] ?? null;

        if ($type === null || $messageId === null || $email === null) {
            return response()->json(['ok' => true]);
        }

        $delivery = Delivery::where('provider_message_id', $messageId)->first();
        if ($delivery === null) {
            return response()->json(['ok' => true]);
        }

        $reason = match ($type) {
            'Bounce' => SuppressionReason::Bounce,
            'Complaint' => SuppressionReason::Complaint,
            default => null,
        };

        if ($reason === null) {
            return response()->json(['ok' => true]);
        }

        Suppression::updateOrCreate(
            [
                'workspace_id' => $delivery->workspace_id,
                'email' => $email,
            ],
            [
                'reason' => $reason,
                'source' => $data,
            ]
        );

        $contact = $delivery->contact;
        $contact->status = $reason === SuppressionReason::Bounce
            ? ContactStatus::Bounced
            : ContactStatus::Complained;
        $contact->save();

        $delivery->status = $reason === SuppressionReason::Bounce
            ? DeliveryStatus::Bounced
            : DeliveryStatus::Complained;
        $delivery->save();

        return response()->json(['ok' => true]);
    }
}
