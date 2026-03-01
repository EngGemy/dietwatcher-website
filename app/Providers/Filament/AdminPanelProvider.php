<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('280px')

            // Brand / Logo
            ->brandName('Diet Watchers')
            ->brandLogo(asset('assets/images/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('assets/images/logo.png'))

            // Cairo font
            ->font('cairo')

            // Brand Colors
            ->colors([
                'primary' => Color::hex('#0D99FF'),
                'success' => Color::hex('#2BBF4B'),
                'danger'  => Color::Red,
                'warning' => Color::Amber,
                'gray'    => Color::Zinc,
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make(__('admin.navigation_groups.meal_plans'))
                    ->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make(__('admin.navigation_groups.meal_management'))
                    ->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make(__('admin.navigation_groups.content'))
                    ->icon('heroicon-o-document-text'),
                NavigationGroup::make(__('admin.navigation_groups.blog'))
                    ->icon('heroicon-o-newspaper'),
                NavigationGroup::make(__('admin.navigation_groups.faq'))
                    ->icon('heroicon-o-question-mark-circle'),
                NavigationGroup::make(__('admin.navigation_groups.testimonials'))
                    ->icon('heroicon-o-star'),
                NavigationGroup::make(__('admin.navigation_groups.users_permissions'))
                    ->icon('heroicon-o-users'),
                NavigationGroup::make(__('admin.navigation_groups.orders_payments'))
                    ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make('External Data')
                    ->icon('heroicon-o-globe-alt'),
                NavigationGroup::make(__('admin.navigation_groups.settings'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
