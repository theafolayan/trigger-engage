<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\UgcApplicationResource\Pages;

use App\Filament\Business\Resources\UgcApplicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUgcApplication extends CreateRecord
{
    protected static string $resource = UgcApplicationResource::class;
}
