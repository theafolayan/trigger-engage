<?php

declare(strict_types=1);

namespace App\Filament\Business\Resources\UgcTaskResource\Pages;

use App\Filament\Business\Resources\UgcTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUgcTask extends CreateRecord
{
    protected static string $resource = UgcTaskResource::class;
}
