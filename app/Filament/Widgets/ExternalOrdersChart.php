<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ExternalDataService;
use Filament\Widgets\ChartWidget;

class ExternalOrdersChart extends ChartWidget
{
    protected ?string $heading = 'Orders Overview';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $service = app(ExternalDataService::class);
        $orders = $service->getOrders();

        // Group orders by status
        $statusCounts = [];
        foreach ($orders as $order) {
            $status = $order['status'] ?? 'unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        $labels = array_keys($statusCounts);
        $data = array_values($statusCounts);

        if (empty($labels)) {
            $labels = ['No data'];
            $data = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders by Status',
                    'data' => $data,
                    'backgroundColor' => '#0D99FF',
                    'borderColor' => '#0D99FF',
                    'fill' => false,
                ],
            ],
            'labels' => array_map('ucfirst', $labels),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
