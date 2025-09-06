<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactStatus: string
{
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';
    case Complained = 'complained';
}
