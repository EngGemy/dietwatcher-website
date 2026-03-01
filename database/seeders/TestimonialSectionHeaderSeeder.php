<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TestimonialSectionHeader;
use Illuminate\Database\Seeder;

class TestimonialSectionHeaderSeeder extends Seeder
{
    public function run(): void
    {
        TestimonialSectionHeader::query()->delete();

        TestimonialSectionHeader::create([
            'badge_title_en' => 'Testimonials',
            'badge_title_ar' => 'آراء العملاء',
            'title_en' => 'What Our Clients Say',
            'title_ar' => 'ماذا يقول عملاؤنا',
            'subtitle_en' => 'Real stories from real people who transformed their health with Diet Watchers.',
            'subtitle_ar' => 'قصص حقيقية من أشخاص حقيقيين غيروا صحتهم مع دايت واتشرز.',
            'is_active' => true,
        ]);
    }
}
