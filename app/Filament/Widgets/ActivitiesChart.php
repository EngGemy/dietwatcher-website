<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class ActivitiesChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Activities';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => [12, 19, 3, 5, 2, 3, 10, 15, 20, 25, 30, 35],
                    'backgroundColor' => '#36A2EB',
                ],
                [
                    'label' => 'Orders',
                    'data' => [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60],
                    'backgroundColor' => '#FF6384',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
