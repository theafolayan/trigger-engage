<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactListResource\Pages;

use App\Filament\Resources\ContactListResource;
use Filament\Resources\Pages\EditRecord;

class EditContactList extends EditRecord
{
    protected static string $resource = ContactListResource::class;
}
