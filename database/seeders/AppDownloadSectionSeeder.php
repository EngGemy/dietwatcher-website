<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppDownloadSection;
use Illuminate\Database\Seeder;

class AppDownloadSectionSeeder extends Seeder
{
    public function run(): void
    {
        AppDownloadSection::query()->delete();

        AppDownloadSection::create([
            'badge_title_en' => 'Mobile App',
            'badge_title_ar' => 'تطبيق الجوال',
            'title_en' => 'Get the Diet Watchers App',
            'title_ar' => 'حمّل تطبيق دايت واتشرز',
            'subtitle_en' => 'Order meals, track your progress, and manage your subscription — all from your phone. Available on iOS and Android.',
            'subtitle_ar' => 'اطلب وجباتك، تابع تقدمك، وأدر اشتراكك — كل ذلك من هاتفك. متاح على iOS و Android.',
            'is_active' => true,
        ]);
    }
}
