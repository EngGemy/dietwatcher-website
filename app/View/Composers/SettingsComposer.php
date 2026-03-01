<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Settings\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Composes data for views using site settings.
 */
class SettingsComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $locale = app()->getLocale();
        
        $view->with([
            'siteName' => Setting::getValue('site_name', config('app.name')),
            'siteLogo' => $this->getImageUrl('logo_header', 'assets/images/logo.png'),
            'siteFavicon' => $this->getImageUrl('favicon', 'assets/images/logo.png'),
            'contactEmail' => Setting::getValue('contact_email', ''),
            'contactPhone' => Setting::getValue('contact_phone', ''),
            'contactAddress' => Setting::getValue('contact_address', ''),
            
            // Footer settings
            'footerLogo' => $this->getImageUrl('logo_footer', 'assets/images/logo.png'),
            'footerDescription' => Setting::getValue('footer_description_' . $locale, 'Healthy Meals Delivered Daily. Designed for Your Goals.'),
            'copyright' => Setting::getValue('copyright_' . $locale, '© ' . date('Y') . ' Diet Watchers. All rights reserved.'),
            
            // Social links
            'socialInstagram' => Setting::getValue('social_instagram', '#'),
            'socialFacebook' => Setting::getValue('social_facebook', '#'),
            'socialTwitter' => Setting::getValue('social_twitter', '#'),
            'socialYouTube' => Setting::getValue('social_youtube', '#'),
            'socialLinkedIn' => Setting::getValue('social_linkedin', '#'),
            
            // App links
            'appStoreUrl' => Setting::getValue('app_store_url', '#'),
            'playStoreUrl' => Setting::getValue('play_store_url', '#'),
            
            // SEO
            'metaTitle' => Setting::getValue('meta_title_' . $locale, ''),
            'metaDescription' => Setting::getValue('meta_description_' . $locale, ''),
        ]);
    }
    
    /**
     * Get image URL from setting or fallback.
     */
    protected function getImageUrl(string $key, string $fallback): string
    {
        $value = Setting::getValue($key);
        
        if (empty($value)) {
            return asset($fallback);
        }
        
        // If it's a full URL, return as is
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        
        // If it's a storage path (e.g., settings/logos/logo.png)
        if (str_starts_with($value, 'settings/')) {
            return asset('storage/' . $value);
        }
        
        // Otherwise assume it's an assets path
        return asset($value);
    }
}
