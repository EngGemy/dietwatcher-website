<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ExternalDataService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExternalStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $service = app(ExternalDataService::class);

        $orders = $service->getOrders();
        $programs = $service->getPrograms();
        $subscriptions = $service->getSubscriptions();

        $totalOrders = count($orders);
        $pendingOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending'));
        $completedOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'completed'));
        $totalPrograms = count($programs);
        $totalSubscriptions = count($subscriptions);

        return [
            Stat::make('Programs', number_format($totalPrograms))
                ->description('Available programs')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description($pendingOrders . ' pending')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),

            Stat::make('Completed Orders', number_format($completedOrders))
                ->description('Fulfilled orders')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Subscriptions', number_format($totalSubscriptions))
                ->description('Active subscriptions')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}
