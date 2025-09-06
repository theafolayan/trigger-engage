<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushSettingResource\Pages;

use App\Filament\Resources\PushSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditPushSetting extends EditRecord
{
    protected static string $resource = PushSettingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['api_key']) && $data['api_key'] !== '') {
            $data['api_key_encrypted'] = encrypt($data['api_key']);
        }

        unset($data['api_key']);

        return $data;
    }
}

