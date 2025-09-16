<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactListResource\Pages;

use App\Filament\Resources\ContactListResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactList extends CreateRecord
{
    protected static string $resource = ContactListResource::class;
}
