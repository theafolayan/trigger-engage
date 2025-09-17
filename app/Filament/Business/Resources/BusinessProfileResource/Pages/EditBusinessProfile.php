<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\BusinessProfileResource\Pages;

use App\Filament\Business\Resources\BusinessProfileResource;
use Filament\Resources\Pages\EditRecord;

class EditBusinessProfile extends EditRecord
{
    protected static string $resource = BusinessProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
