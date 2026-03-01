<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Settings\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Placeholder image URLs so logos/favicon are visible in admin.
     */
    private const PLACEHOLDER_LOGO = 'https://placehold.co/200x60/0f766e/ffffff?text=Logo';

    private const PLACEHOLDER_FAVICON = 'https://placehold.co/32x32/0f766e/ffffff?text=F';

    public function run(): void
    {
        // General settings
        Setting::setValue('site_name', 'Diet Watchers', 'general');
        Setting::setValue('contact_email', 'contact@dietwatcher.example', 'general');
        Setting::setValue('copyright_en', '© ' . date('Y') . ' Diet Watchers. All rights reserved.', 'general');
        Setting::setValue('copyright_ar', '© ' . date('Y') . ' ديت واتشرز. جميع الحقوق محفوظة.', 'general');
        
        // Header settings
        Setting::setValue('logo_header', self::PLACEHOLDER_LOGO, 'header', 'string');
        
        // Footer settings
        Setting::setValue('logo_footer', self::PLACEHOLDER_LOGO, 'footer', 'string');
        Setting::setValue('footer_description_en', 'Healthy Meals Delivered Daily. Designed for Your Goals.', 'footer');
        Setting::setValue('footer_description_ar', 'وجبات صحية تُسلم يومياً. مصممة لأهدافك.', 'footer');
        Setting::setValue('social_instagram', '#', 'social');
        Setting::setValue('social_facebook', '#', 'social');
        Setting::setValue('social_twitter', '#', 'social');
        Setting::setValue('social_youtube', '#', 'social');
        Setting::setValue('app_store_url', '#', 'footer');
        Setting::setValue('play_store_url', '#', 'footer');
        
        // Favicon
        Setting::setValue('favicon', self::PLACEHOLDER_FAVICON, 'general', 'string');

        // Checkout settings
        Setting::setValue('vat_rate', '15', 'checkout');
        Setting::setValue('delivery_fee', '25', 'checkout');
    }
}
