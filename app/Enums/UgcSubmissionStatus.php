<?php

declare(strict_types=1);

namespace App\Enums;

enum UgcSubmissionStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case RevisionsRequested = 'revisions_requested';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::RevisionsRequested => 'Revisions Requested',
            self::Rejected => 'Rejected',
        };
    }
}
