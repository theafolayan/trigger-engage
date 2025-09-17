<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\UgcSubmissionResource\Pages;

use App\Filament\Business\Resources\UgcSubmissionResource;
use Filament\Resources\Pages\EditRecord;

class EditUgcSubmission extends EditRecord
{
    protected static string $resource = UgcSubmissionResource::class;
}
