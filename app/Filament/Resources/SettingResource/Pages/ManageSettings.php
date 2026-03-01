<?php

declare(strict_types=1);

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Settings\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ManageSettings extends Page
{
    protected static string $resource = SettingResource::class;
    protected string $view = 'filament.resources.setting-resource.pages.manage-settings';

    public ?array $data = [];

    /**
     * All settings keys that we manage
     */
    protected array $settingsKeys = [
        // General
        'site_name',
        'contact_email',
        'contact_phone',
        'contact_address',
        
        // Logos
        'logo_header',
        'logo_footer',
        'favicon',
        
        // Footer
        'footer_description_en',
        'footer_description_ar',
        'copyright_en',
        'copyright_ar',
        
        // Social
        'social_instagram',
        'social_facebook',
        'social_twitter',
        'social_youtube',
        'social_linkedin',
        
        // Apps
        'app_store_url',
        'play_store_url',
        
        // Checkout
        'delivery_fee',
        'vat_rate',

        // SEO
        'meta_title_en',
        'meta_title_ar',
        'meta_description_en',
        'meta_description_ar',
    ];

    public function mount(): void
    {
        $this->form->fill($this->getCurrentSettings());
    }

    protected function getCurrentSettings(): array
    {
        $settings = [];
        
        foreach ($this->settingsKeys as $key) {
            $value = Setting::getValue($key);
            
            // Handle file uploads - convert path to array format for Filament
            if (in_array($key, ['logo_header', 'logo_footer', 'favicon']) && $value) {
                // Extract just the filename for the file upload component
                $settings[$key] = $value ? [$value] : [];
            } else {
                $settings[$key] = $value;
            }
        }
        
        return $settings;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        foreach ($this->settingsKeys as $key) {
            $value = $data[$key] ?? null;
            
            // Handle file uploads
            if (in_array($key, ['logo_header', 'logo_footer', 'favicon'])) {
                if (is_array($value) && count($value) > 0) {
                    $value = $value[0]; // Get the first (and only) file path
                } else {
                    $value = null;
                }
            }
            
            // Determine group based on key
            $group = $this->getGroupForKey($key);
            
            Setting::setValue($key, $value, $group);
        }
        
        // Clear cache
        Setting::clearCache();
        
        Notification::make()
            ->title(__('admin.settings.notifications.saved'))
            ->success()
            ->send();
    }

    protected function getGroupForKey(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'logo_') || $key === 'favicon' => 'logo',
            str_starts_with($key, 'footer_') || str_starts_with($key, 'copyright_') => 'footer',
            str_starts_with($key, 'social_') => 'social',
            str_starts_with($key, 'app_') || str_starts_with($key, 'play_') => 'apps',
            str_starts_with($key, 'meta_') => 'seo',
            $key === 'delivery_fee' || $key === 'vat_rate' => 'checkout',
            default => 'general',
        };
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.settings.actions.save'))
                ->submit('save')
                ->color('primary'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
