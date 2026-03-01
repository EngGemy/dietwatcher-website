<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ExternalDataService;
use Filament\Widgets\Widget;

class LatestExternalOrders extends Widget
{
    protected string $view = 'filament.widgets.latest-external-orders';

    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    public function getOrders(): array
    {
        try {
            $service = app(ExternalDataService::class);
            return array_slice($service->getOrders(), 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }
}
