<?php

declare(strict_types=1);

namespace App\Filament\Resources\SmtpSettingResource\Pages;

use App\Filament\Resources\SmtpSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmtpSetting extends CreateRecord
{
    protected static string $resource = SmtpSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['password'] ?? '') !== '') {
            $data['password_encrypted'] = encrypt($data['password']);
        }

        unset($data['password']);

        return $data;
    }
}

