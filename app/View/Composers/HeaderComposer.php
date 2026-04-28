<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\MenuItem;
use App\Services\ExternalDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Composes data for the header partial view.
 *
 * Provides menu items and configuration to the header from database.
 */
class HeaderComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view): void
    {
        $view->with([
            'headerMenu' => $this->getHeaderMenu(),
            'headerActions' => $this->getHeaderActions(),
            'availableLocales' => $this->getAvailableLocales(),
            'currentLocale' => app()->getLocale(),
            'planCategoryLookup' => $this->getPlanCategoryLookup(),
        ]);
    }

    /**
     * Build a lookup [normalized-name => category-id] from the external API
     * so the header can rewrite each "Meal Plans" dropdown sub-item to the
     * matching `?category=<id>` deep-link.
     */
    protected function getPlanCategoryLookup(): array
    {
        return Cache::remember('plan_category_lookup', 1800, function () {
            $categories = app(ExternalDataService::class)->getCategories();
            $lookup = [];

            $normalize = fn (string $s) => mb_strtolower(trim($s));

            // Only register categories that actually contain programs — empty
            // ones must NOT appear in the header dropdown.
            $hasCounts = collect($categories)->contains(fn ($c) => (int) ($c['programs_count'] ?? 0) > 0);

            foreach ($categories as $cat) {
                $id = (int) ($cat['id'] ?? 0);
                if (! $id) {
                    continue;
                }

                if ($hasCounts && (int) ($cat['programs_count'] ?? 0) === 0) {
                    continue;
                }

                $names = $cat['name'] ?? '';
                $candidates = is_array($names) ? array_values($names) : [$names];

                foreach ($candidates as $name) {
                    $key = $normalize((string) $name);
                    if ($key !== '') {
                        $lookup[$key] = $id;
                    }
                }
            }

            return $lookup;
        });
    }

    /**
     * Get header menu items from database.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getHeaderMenu()
    {
        return Cache::remember('header_menu_' . app()->getLocale(), 3600, function () {
            return MenuItem::active()
                ->location('header')
                ->topLevel()
                ->with('children')
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Get header action buttons from database.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getHeaderActions()
    {
        return Cache::remember('header_actions_' . app()->getLocale(), 3600, function () {
            return MenuItem::active()
                ->location('header_actions')
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Get available locales for language switcher.
     *
     * @return array<string, string>
     */
    protected function getAvailableLocales(): array
    {
        return config('app.available_locales', [
            'en' => 'English',
            'ar' => 'العربية',
        ]);
    }
}

