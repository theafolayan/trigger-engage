<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAutomation extends EditRecord
{
    protected static string $resource = AutomationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $automation = $this->getRecord();

        $data['conditions'] = AutomationResource::prepareConditionsForForm($automation->conditions ?? []);
        $data['steps'] = AutomationResource::prepareStepsForForm($automation);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data = AutomationResource::normalizeFormData($data);

        $steps = $data['steps'] ?? [];
        unset($data['steps']);

        AutomationResource::validateSteps($steps);

        $record->update($data);

        AutomationResource::syncSteps($record, $steps);

        return $record;
    }
}
