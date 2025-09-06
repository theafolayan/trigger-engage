<?php

declare(strict_types=1);

namespace App\Filament\Resources\SmtpSettingResource\Pages;

use App\Filament\Resources\SmtpSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditSmtpSetting extends EditRecord
{
    protected static string $resource = SmtpSettingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password_encrypted'] = encrypt($data['password']);
        }

        unset($data['password']);

        return $data;
    }
}
