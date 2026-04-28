<?php

declare(strict_types=1);

namespace App\Livewire\MealPlans;

use App\Models\Settings\Setting;
use App\Services\ExternalDataService;
use Illuminate\Support\Facades\Request;
use Livewire\Attributes\Url;
use Livewire\Component;

class FilterPlans extends Component
{
    #[Url(as: 'category', except: null)]
    public ?int $selectedCategory = null;

    public string $selectedMealType = '';
    public string $pageTitle = '';
    public string $pageDescription = '';

    public function mount(): void
    {
        $locale = app()->getLocale();

        // Allow deep-linking to a pre-filtered view via ?category=<id|slug>
        $param = Request::query('category');
        if ($param !== null && $param !== '') {
            if (is_numeric($param)) {
                $this->selectedCategory = (int) $param;
            } else {
                $this->selectedCategory = $this->resolveCategorySlug((string) $param);
            }
        }

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

    /**
     * Resolve a slug like "weight-loss" to a category id by matching against
     * the category names returned by the API (case/locale insensitive).
     */
    private function resolveCategorySlug(string $slug): ?int
    {
        $normalize = fn (string $s) => mb_strtolower(
            preg_replace('/[\s_]+/u', '-', trim($s)) ?? ''
        );

        $needle = $normalize($slug);

        foreach (app(ExternalDataService::class)->getCategories() as $cat) {
            $names = $cat['name'] ?? '';
            $candidates = is_array($names) ? array_values($names) : [$names];

            foreach ($candidates as $name) {
                if ($normalize((string) $name) === $needle) {
                    return (int) ($cat['id'] ?? 0) ?: null;
                }
            }
        }

        return null;
    }
}
