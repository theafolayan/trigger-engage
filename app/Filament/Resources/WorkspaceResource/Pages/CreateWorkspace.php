<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkspaceResource\Pages;

use App\Filament\Resources\WorkspaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkspace extends CreateRecord
{
    protected static string $resource = WorkspaceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['account_id'] = auth()->user()->account_id;

        return $data;
    }
}

