<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\UgcApplicationResource\Pages;

use App\Filament\Business\Resources\UgcApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListUgcApplications extends ListRecords
{
    protected static string $resource = UgcApplicationResource::class;
}
