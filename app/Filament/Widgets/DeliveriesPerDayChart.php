<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\StatsService;
use Filament\Widgets\LineChartWidget;

class DeliveriesPerDayChart extends LineChartWidget
{
    protected ?string $heading = 'Deliveries per day';

    protected function getData(): array
    {
        $data = app(StatsService::class)->deliveriesPerDay();

        return [
            'datasets' => [
                [
                    'label' => 'Deliveries',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }
}
