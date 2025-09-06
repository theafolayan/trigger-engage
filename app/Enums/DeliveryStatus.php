<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Failed = 'failed';
}
