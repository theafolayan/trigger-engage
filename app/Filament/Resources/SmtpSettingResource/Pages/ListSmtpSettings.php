<?php

declare(strict_types=1);

namespace App\Filament\Resources\SmtpSettingResource\Pages;

use App\Filament\Resources\SmtpSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListSmtpSettings extends ListRecords
{
    protected static string $resource = SmtpSettingResource::class;
}

