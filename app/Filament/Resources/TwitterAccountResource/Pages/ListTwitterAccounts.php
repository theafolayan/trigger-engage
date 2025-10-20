<?php

declare(strict_types=1);

namespace App\Filament\Resources\TwitterAccountResource\Pages;

use App\Filament\Resources\TwitterAccountResource;
use Filament\Resources\Pages\ListRecords;

class ListTwitterAccounts extends ListRecords
{
    protected static string $resource = TwitterAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
