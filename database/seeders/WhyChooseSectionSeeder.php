<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WhyChooseSection;
use Illuminate\Database\Seeder;

class WhyChooseSectionSeeder extends Seeder
{
    public function run(): void
    {
        WhyChooseSection::query()->delete();

        WhyChooseSection::create([
            'badge_title_en' => 'Why Diet Watchers?',
            'badge_title_ar' => 'لماذا دايت واتشرز؟',
            'title_en' => 'Choosing Diet Watchers',
            'title_ar' => 'اختيار دايت واتشرز',
            'subtitle_en' => 'We simplifies healthy eating with fresh meals, expert plans, and flexible options to help you feel your best.',
            'subtitle_ar' => 'نبسّط الأكل الصحي بوجبات طازجة وخطط متخصصة وخيارات مرنة لمساعدتك على الشعور بأفضل حال.',
            'is_active' => true,
        ]);
    }
}
