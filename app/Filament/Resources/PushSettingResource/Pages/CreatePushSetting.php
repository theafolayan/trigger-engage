<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushSettingResource\Pages;

use App\Filament\Resources\PushSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePushSetting extends CreateRecord
{
    protected static string $resource = PushSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['api_key'] ?? '') !== '') {
            $data['api_key_encrypted'] = encrypt($data['api_key']);
        }

        unset($data['api_key']);

        return $data;
    }
}

