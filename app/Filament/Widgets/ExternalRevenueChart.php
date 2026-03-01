<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ExternalDataService;
use Filament\Widgets\ChartWidget;

class ExternalRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Overview';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $service = app(ExternalDataService::class);
        $orders = $service->getOrders();

        // Sum revenue from completed orders
        $revenue = 0;
        $orderCount = 0;
        foreach ($orders as $order) {
            if (($order['status'] ?? '') === 'completed') {
                $revenue += (float) ($order['total'] ?? $order['total_amount'] ?? 0);
                $orderCount++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (SAR)',
                    'data' => [$revenue],
                    'backgroundColor' => 'rgba(13, 153, 255, 0.2)',
                    'borderColor' => '#0D99FF',
                    'fill' => true,
                ],
                [
                    'label' => 'Completed Orders',
                    'data' => [$orderCount],
                    'backgroundColor' => 'rgba(43, 191, 75, 0.2)',
                    'borderColor' => '#2BBF4B',
                    'fill' => true,
                ],
            ],
            'labels' => ['Total'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
