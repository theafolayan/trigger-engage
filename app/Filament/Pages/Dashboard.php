<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\DeliveriesPerDayChart::class,
            \App\Filament\Widgets\BounceComplaintChart::class,
            \App\Filament\Widgets\TopAutomationsChart::class,
            \App\Filament\Widgets\HorizonHealthWidget::class,
        ];
    }
}
