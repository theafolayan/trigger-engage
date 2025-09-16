<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use App\Models\Automation;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomation extends CreateRecord
{
    protected static string $resource = AutomationResource::class;

    protected function handleRecordCreation(array $data): Automation
    {
        $data = AutomationResource::normalizeFormData($data);

        $steps = $data['steps'] ?? [];
        unset($data['steps']);

        AutomationResource::validateSteps($steps);

        $automation = Automation::create($data);

        AutomationResource::syncSteps($automation, $steps);

        return $automation;
    }
}
