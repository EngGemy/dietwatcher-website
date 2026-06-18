<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cached bilingual daily wellness tips (Gemini with static fallback).
 */
class DailyHealthTipsService
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * @return array<int, string>
     */
    public function tips(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $cacheKey = 'daily_health_tips_'.$locale.'_'.now()->toDateString();

        return Cache::remember($cacheKey, now()->endOfDay()->addHour(), function () use ($locale): array {
            $generated = $this->generateWithGemini($locale);

            return $generated !== [] ? $generated : $this->fallbackTips($locale);
        });
    }

    public function isGeminiPowered(?string $locale = null): bool
    {
        $locale = $locale ?: app()->getLocale();
        $cacheKey = 'daily_health_tips_source_'.$locale.'_'.now()->toDateString();

        return (bool) Cache::get($cacheKey, false);
    }

    /**
     * @return array<int, string>
     */
    private function generateWithGemini(string $locale): array
    {
        if (! $this->gemini->isConfigured()) {
            return [];
        }

        $language = $locale === 'ar' ? 'Arabic' : 'English';
        $system = <<<PROMPT
You write short daily nutrition and healthy-lifestyle tips for Diet Watchers customers in Saudi Arabia.
Return ONLY valid JSON: {"tips":["tip1","tip2",...]} with exactly 10 tips.
Each tip must be one concise sentence (max 90 characters), practical, positive, and {$language}.
No markdown, emojis, or numbering inside strings.
PROMPT;

        $raw = $this->gemini->generate($system, [
            ['role' => 'user', 'content' => 'Generate today\'s rotating wellness tips.'],
        ], jsonMode: true, temperature: 0.55);

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! is_array($decoded['tips'] ?? null)) {
            Log::warning('DailyHealthTipsService: invalid Gemini JSON');

            return [];
        }

        $tips = array_values(array_filter(array_map(
            static fn (mixed $tip): string => trim((string) $tip),
            $decoded['tips'],
        ), static fn (string $tip): bool => $tip !== ''));

        if (count($tips) < 4) {
            return [];
        }

        Cache::put('daily_health_tips_source_'.$locale.'_'.now()->toDateString(), true, now()->endOfDay()->addHour());

        return array_slice($tips, 0, 12);
    }

    /**
     * @return array<int, string>
     */
    private function fallbackTips(string $locale): array
    {
        if ($locale === 'ar') {
            return [
                'اشرب كوب ماء عند الاستيقاظ لتنشيط عملية الأيض.',
                'وزّع وجباتك على 3–4 وجبات يومياً لتفادي الجوع المفاجئ.',
                'أضف الخضار لكل وجبة رئيسية لزيادة الألياف والشبع.',
                'نام 7–8 ساعات؛ النوم الجيد يدعم التحكم بالوزن.',
                'اختر وجبات غنية بالبروتين في الإفطار لتثبيت الطاقة.',
                'قلّل المشروبات السكرية واستبدلها بالماء أو الشاي بدون سكر.',
                'خطّط وجباتك مسبقاً لتقليل الاعتماد على الطلبات السريعة.',
                'امشِ 20 دقيقة يومياً؛ الحركة البسيطة تصنع فرقاً كبيراً.',
                'تناول البروتين بعد التمرين لدعم تعافي العضلات.',
                'استمتع بطعامك ببطء وبدون شاشات لتحسين الشبع.',
            ];
        }

        return [
            'Drink a glass of water after waking up to kick-start metabolism.',
            'Split meals into 3–4 portions to avoid sudden hunger spikes.',
            'Add vegetables to every main meal for fiber and fullness.',
            'Aim for 7–8 hours of sleep to support weight management.',
            'Choose a protein-rich breakfast to stabilize morning energy.',
            'Swap sugary drinks for water or unsweetened tea.',
            'Plan meals ahead to reduce reliance on fast food.',
            'Walk 20 minutes daily — small movement adds up quickly.',
            'Include protein after workouts to support muscle recovery.',
            'Eat slowly and without screens to improve satiety.',
        ];
    }
}
