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
        $mealData = $service->getMeal($meal);

        abort_unless($mealData, 404);

        $relatedMeals = $service->getRelatedMeals(
            (int) $mealData['id'],
            $mealData['group_id'] ?? null,
            8
        );

        return view('pages.meal-detail', [
            'meal' => $mealData,
            'relatedMeals' => $relatedMeals,
        ]);
    }
}
