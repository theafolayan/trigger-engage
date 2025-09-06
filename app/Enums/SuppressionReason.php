<?php

declare(strict_types=1);

namespace App\Enums;

enum SuppressionReason: string
{
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Manual = 'manual';
}
