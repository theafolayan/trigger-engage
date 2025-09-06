<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\StatsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = app(StatsService::class)->totals();

        return [
            Stat::make('Contacts', (string) $stats['contacts']['total']),
            Stat::make('Deliveries', (string) $stats['deliveries']['sent']),
            Stat::make('Events', (string) $stats['events_ingested']),
        ];
    }
}
