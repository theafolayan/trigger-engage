<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\BusinessProfileResource\Pages;

use App\Filament\Business\Resources\BusinessProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinessProfiles extends ListRecords
{
    protected static string $resource = BusinessProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();

        $record = (clone $this->getTableQuery())->first();

        if ($record !== null) {
            $this->redirect(BusinessProfileResource::getUrl('edit', ['record' => $record]));
        }
    }
}
