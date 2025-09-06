<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\StatsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HorizonHealthWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $health = app(StatsService::class)->systemHealth();

        return [
            Stat::make('Queue', (string) $health['queue_size']),
            Stat::make('Failed Jobs', (string) $health['failed_jobs']),
            Stat::make('Horizon', (string) $health['horizon_status']),
        ];
    }
}
