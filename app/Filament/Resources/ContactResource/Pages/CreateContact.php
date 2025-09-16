<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Services\UsageTracker;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function afterCreate(): void
    {
        $workspace = currentWorkspace();

        if ($workspace !== null) {
            app(UsageTracker::class)->recordContactsCreated($workspace);
        }
    }
}
