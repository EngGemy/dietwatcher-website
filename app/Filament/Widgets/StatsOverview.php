<?php

namespace App\Filament\Widgets;

use App\Models\BlogPost;
use App\Models\Meal;
use App\Models\Plan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('New users joined')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Active Plans', Plan::where('is_active', true)->count())
                ->description('Total active diet plans')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Store Meals', Meal::count())
                ->description('Available meals in store')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),

            Stat::make('Published Articles', BlogPost::where('status', 'published')->count())
                ->description('Live blog posts')
                ->color('info'),
        ];
    }
}
