<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\StatsService;
use Filament\Widgets\BarChartWidget;

class TopAutomationsChart extends BarChartWidget
{
    protected ?string $heading = 'Top Automations';

    protected function getData(): array
    {
        $data = app(StatsService::class)->topAutomations();

        return [
            'datasets' => [
                [
                    'label' => 'Sends',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }
}
