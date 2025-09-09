<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushSettingResource\Pages;

use App\Filament\Resources\PushSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListPushSettings extends ListRecords
{
    protected static string $resource = PushSettingResource::class;
}

