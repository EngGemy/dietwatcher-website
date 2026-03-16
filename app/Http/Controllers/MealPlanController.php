<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExternalDataService;

class MealPlanController extends Controller
{
    protected ExternalDataService $externalDataService;

    public function __construct(ExternalDataService $externalDataService)
    {
        $this->externalDataService = $externalDataService;
    }

    /**
     * Display the meal plans page (handled by Livewire FilterPlans component).
     */
    public function index()
    {
        return view('pages.meal-plans');
    }

    /**
     * Display a single meal plan detail.
     */
    public function show(string $id)
    {
        $plan = $this->externalDataService->getProgram((int) $id);

        if (! $plan) {
            return redirect()->route('meal-plans.index')
                ->with('info', __('Plan details coming soon.'));
        }

        // Fetch dynamic calorie options and durations from API
        $apiCalories = $this->externalDataService->getPlanCalories((int) $id);
        $apiDurations = $this->externalDataService->getPlanDurations((int) $id);

        return view('pages.meal-plan-detail', compact('plan', 'apiCalories', 'apiDurations'));
    }
}
