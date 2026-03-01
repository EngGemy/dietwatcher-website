<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\MenuItem;
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
        ]);
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

