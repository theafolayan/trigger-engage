<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\UgcTaskResource\Pages;

use App\Filament\Business\Resources\UgcTaskResource;
use Filament\Resources\Pages\ListRecords;

class ListUgcTasks extends ListRecords
{
    protected static string $resource = UgcTaskResource::class;
}
