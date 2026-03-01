<?php

namespace Database\Seeders;

use App\Models\Settings\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name', 'value' => 'Diet Watchers', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'info@dietwatchers.com', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+966 50 123 4567', 'group' => 'general'],
            ['key' => 'contact_address', 'value' => 'Riyadh, Saudi Arabia', 'group' => 'general'],
            
            // Footer
            ['key' => 'footer_description_en', 'value' => 'Healthy Meals Delivered Daily. Designed for Your Goals.', 'group' => 'footer'],
            ['key' => 'footer_description_ar', 'value' => 'وجبات صحية تصلك يومياً. مصممة خصيصاً لأهدافك.', 'group' => 'footer'],
            ['key' => 'copyright_en', 'value' => '© ' . date('Y') . ' Diet Watchers. All rights reserved.', 'group' => 'footer'],
            ['key' => 'copyright_ar', 'value' => '© ' . date('Y') . ' دايت ووتشرز. جميع الحقوق محفوظة.', 'group' => 'footer'],
            
            // Social
            ['key' => 'social_instagram', 'value' => '#', 'group' => 'social'],
            ['key' => 'social_facebook', 'value' => '#', 'group' => 'social'],
            ['key' => 'social_twitter', 'value' => '#', 'group' => 'social'],
            ['key' => 'social_youtube', 'value' => '#', 'group' => 'social'],
            ['key' => 'social_linkedin', 'value' => '#', 'group' => 'social'],
            
            // Apps
            ['key' => 'app_store_url', 'value' => '#', 'group' => 'apps'],
            ['key' => 'play_store_url', 'value' => '#', 'group' => 'apps'],
            
            // SEO
            ['key' => 'meta_title_en', 'value' => 'Diet Watchers - Healthy Meals Delivered Daily', 'group' => 'seo'],
            ['key' => 'meta_title_ar', 'value' => 'دايت ووتشرز - وجبات صحية تصلك يومياً', 'group' => 'seo'],
            ['key' => 'meta_description_en', 'value' => 'Chef-made, calorie-smart meals delivered in Saudi Arabia. Plans online, managed via our app.', 'group' => 'seo'],
            ['key' => 'meta_description_ar', 'value' => 'وجبات مصنوعة من الطهاة، ذات سعرات حرارية ذكية، توصل في السعودية. خطط اونلاين، إدارة عبر تطبيقنا.', 'group' => 'seo'],

            // Checkout
            ['key' => 'vat_rate', 'value' => '15', 'group' => 'checkout'],
            ['key' => 'delivery_fee', 'value' => '25', 'group' => 'checkout'],
        ];

        foreach ($settings as $setting) {
            Setting::setValue($setting['key'], $setting['value'], $setting['group']);
        }
    }
}
