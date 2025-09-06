<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\StatsService;
use Filament\Widgets\LineChartWidget;

class BounceComplaintChart extends LineChartWidget
{
    protected ?string $heading = 'Bounces & Complaints';

    protected function getData(): array
    {
        $data = app(StatsService::class)->bouncesComplaintsTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Bounced',
                    'data' => array_values($data['bounced']),
                ],
                [
                    'label' => 'Complained',
                    'data' => array_values($data['complained']),
                ],
            ],
            'labels' => array_keys($data['bounced'] + $data['complained']),
        ];
    }
}
