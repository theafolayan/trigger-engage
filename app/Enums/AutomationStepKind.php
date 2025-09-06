<?php

declare(strict_types=1);

namespace App\Enums;

enum AutomationStepKind: string
{
    case Delay = 'delay';
    case SendEmail = 'send_email';
    case Branch = 'branch';
    case Exit = 'exit';
}
