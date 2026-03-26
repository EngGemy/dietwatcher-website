<?php

declare(strict_types=1);

namespace App\Livewire\MealPlans;

use App\Models\Settings\Setting;
use App\Services\ExternalDataService;
use Livewire\Component;

class FilterPlans extends Component
{
    public ?int $selectedCategory = null;
    public string $selectedMealType = '';
    public string $pageTitle = '';
    public string $pageDescription = '';

    public function mount(): void
    {
        $locale = app()->getLocale();

        $this->pageTitle = Setting::getValue('meal_plans_title_' . $locale,
            $locale === 'ar'
                ? 'اختر خطة الوجبات التي تناسب أسلوب حياتك'
                : 'Choose the Meal Plan That Fits Your Lifestyle'
        );

        $this->pageDescription = Setting::getValue('meal_plans_description_' . $locale,
            $locale === 'ar'
                ? 'جميع خطط Diet Watchers معتمدة من أخصائيي التغذية ومراقبة السعرات الحرارية وقابلة للإدارة بالكامل من خلال تطبيق الهاتف المحمول.'
                : 'All Diet Watchers plans are nutritionist-approved, calorie-controlled, and fully manageable through our mobile app.'
        );
    }

    public function filterByCategory(?int $categoryId): void
    {
        $this->selectedCategory = $categoryId;
    }

    public function filterByMealType(string $type): void
    {
        $this->selectedMealType = $type;
    }

    public function render()
    {
        $service = app(ExternalDataService::class);

        $plans = $service->getPrograms($this->selectedCategory);

        $categories = $service->getCategories();

        return view('livewire.meal-plans.filter-plans', [
            'plans'      => $plans,
            'categories' => $categories,
        ]);
    }
}
