<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Content\HeroSection;
use App\Models\Content\HeroSectionTranslation;
use Illuminate\Database\Seeder;

class HeroSectionSeeder extends Seeder
{
    /**
     * Placeholder image base URL (visible in admin and frontend).
     */
    private const PLACEHOLDER_IMAGE = 'https://placehold.co/1200x800/0ea5e9/ffffff?text=Hero+Desktop';

    private const PLACEHOLDER_MOBILE = 'https://placehold.co/400x700/0ea5e9/ffffff?text=Hero+Mobile';

    public function run(): void
    {
        $hero = HeroSection::create([
            'order' => 0,
            'image_desktop' => self::PLACEHOLDER_IMAGE,
            'image_mobile' => self::PLACEHOLDER_MOBILE,
            'app_store_url' => 'https://apps.apple.com/app/example',
            'play_store_url' => 'https://play.google.com/store/apps/details?id=example',
            'is_active' => true,
        ]);

        HeroSectionTranslation::create([
            'hero_section_id' => $hero->id,
            'locale' => 'en',
            'title' => 'Healthy Meals Delivered Daily. Designed for Your Goals.',
            'subtitle' => 'Choose your meals, set your schedule, and we deliver fresh, chef-prepared food to your door.',
            'cta_text' => 'App Store',
            'cta_secondary_text' => 'Google Play',
        ]);

        HeroSectionTranslation::create([
            'hero_section_id' => $hero->id,
            'locale' => 'ar',
            'title' => 'وجبات صحية يومياً. مصممة لأهدافك.',
            'subtitle' => 'اختر وجباتك، حدد مواعيدك، ونوصل لك طعاماً طازجاً معداً من الطهاة.',
            'cta_text' => 'متجر التطبيقات',
            'cta_secondary_text' => 'جوجل بلاي',
        ]);
    }
}
