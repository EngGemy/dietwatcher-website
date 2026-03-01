<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FaqSectionHeaderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\FaqSectionHeader::create([
            'badge_title_en' => 'Answers',
            'badge_title_ar' => 'الإجابات',
            'title_en' => 'Frequently Asked Questions',
            'title_ar' => 'الأسئلة الشائعة',
            'subtitle_en' => 'Get answers to frequently asked questions.',
            'subtitle_ar' => 'احصل على إجابات للأسئلة الأكثر شيوعًا.',
            'is_active' => true,
        ]);
    }
}
