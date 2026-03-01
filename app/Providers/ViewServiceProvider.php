<?php

declare(strict_types=1);

namespace App\Providers;

use App\View\Composers\HeaderComposer;
use App\View\Composers\SettingsComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * View Service Provider for registering view composers.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register header composer for the header partial
        View::composer('partials.header', HeaderComposer::class);
        
        // Register settings composer for header, footer, and all pages
        View::composer(['partials.header', 'partials.footer', 'layouts.app', 'pages.*'], SettingsComposer::class);
    }
}
