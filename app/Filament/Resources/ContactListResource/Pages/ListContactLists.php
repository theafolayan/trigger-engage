<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactListResource\Pages;

use App\Filament\Resources\ContactListResource;
use Filament\Resources\Pages\ListRecords;

class ListContactLists extends ListRecords
{
    protected static string $resource = ContactListResource::class;
}
