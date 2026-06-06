<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExternalDataService;
use Illuminate\Contracts\View\View;

class MarketMealController extends Controller
{
    public function show(int $meal): View
    {
        $service = app(ExternalDataService::class);
        $detail = $service->getMealDetail($meal);

        abort_unless($detail, 404);

        return view('pages.meal-detail', [
            'meal' => $detail['meal'],
            'relatedMeals' => $detail['related'],
        ]);
    }
}
