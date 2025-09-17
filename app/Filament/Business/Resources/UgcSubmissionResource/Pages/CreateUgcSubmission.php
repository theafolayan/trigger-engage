<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\UgcSubmissionResource\Pages;

use App\Filament\Business\Resources\UgcSubmissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUgcSubmission extends CreateRecord
{
    protected static string $resource = UgcSubmissionResource::class;
}
