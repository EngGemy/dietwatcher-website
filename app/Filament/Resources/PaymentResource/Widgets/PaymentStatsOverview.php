<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = Payment::count();
        $paid = Payment::where('status', PaymentStatus::PAID)->count();
        $pending = Payment::where('status', PaymentStatus::PENDING)->count();
        $failed = Payment::where('status', PaymentStatus::FAILED)->count();
        $revenue = Payment::where('status', PaymentStatus::PAID)->sum('amount');

        $successRate = $total > 0 ? round(($paid / $total) * 100, 1) : 0;

        return [
            Stat::make(__('admin.payments.stats.total'), number_format($total))
                ->description(__('admin.payments.stats.total_desc'))
                ->icon('heroicon-o-shopping-cart')
                ->color('gray'),

            Stat::make(__('admin.payments.stats.paid'), number_format($paid))
                ->description($successRate . '% ' . __('admin.payments.stats.success_rate'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('admin.payments.stats.pending'), number_format($pending))
                ->description(__('admin.payments.stats.pending_desc'))
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(__('admin.payments.stats.failed'), number_format($failed))
                ->description(__('admin.payments.stats.failed_desc'))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make(__('admin.payments.stats.revenue'), number_format($revenue / 100, 2) . ' SAR')
                ->description(__('admin.payments.stats.revenue_desc'))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }
}
