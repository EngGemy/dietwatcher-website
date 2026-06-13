<?php

declare(strict_types=1);

namespace App\Livewire\AiAssistant;

use App\Models\Faq;
use App\Models\Settings\Setting;
use App\Models\WhyChooseSection;
use App\Services\ExternalDataService;
use App\Services\GeminiService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class MealAssistant extends Component
{
    private const SESSION_SUPPORT = 'ai_assistant_support_chat';

    public bool $isOpen = false;

    public string $activeTab = 'analysis';

    public int $analysisStep = 1;

    public int $heightCm = 170;

    public float $weightKg = 70;

    public int $age = 30;

    public string $gender = 'male';

    public string $activityLevel = 'moderate';

    public string $goal = 'maintain';

    public string $dietStyle = 'balanced';

    /** @var array<int, string> */
    public array $restrictions = [];

    public int $mealsPerDay = 3;

    public string $healthNotes = '';

    public bool $metricsReady = false;

    public ?float $bmr = null;

    public ?float $tdee = null;

    public ?int $targetCalories = null;

    public ?float $bmi = null;

    public string $bmiCategory = '';

    /** @var array{protein_g: float, carbs_g: float, fat_g: float, protein_pct: int, carbs_pct: int, fat_pct: int} */
    public array $macroTargets = [];

    public bool $loadingReport = false;

    public string $reportSource = '';

    /** @var array<string, mixed> */
    public array $aiReport = [];

    /** @var array<int, array<string, mixed>> */
    public array $recommendations = [];

    public bool $loadingRecommendations = false;

    public string $recommendationSource = '';

    public bool $catalogUnavailable = false;

    public string $supportInput = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $supportMessages = [];

    public bool $loadingSupport = false;

    /** @var array<int, array<string, mixed>> */
    public array $supportMealPicks = [];

    /** @var array<int, array<string, mixed>> */
    public array $supportPlanPicks = [];

    public bool $geminiConfigured = false;

    /** @var array<int, string> */
    public array $quickQuestions = [];

    public string $aboutDescription = '';

    public string $aboutContact = '';

    public string $siteName = '';

    /** @var array<int, array{question: string, answer: string}> */
    public array $aboutFaqs = [];

    public function mount(): void
    {
        $this->siteName = (string) Setting::getValue('site_name', config('app.name'));
        $this->geminiConfigured = app(GeminiService::class)->isConfigured();
        $this->supportMessages = session(self::SESSION_SUPPORT, []);
        $this->quickQuestions = $this->defaultQuickQuestions();
        $this->loadAboutContent();
        $this->calculateMetrics();
    }

    #[On('open-ai-assistant')]
    public function openPanel(): void
    {
        $this->isOpen = true;
    }

    public function togglePanel(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function closePanel(): void
    {
        $this->isOpen = false;
    }

    public function setTab(string $tab): void
    {
        $allowed = ['analysis', 'recommend', 'support', 'about'];
        if (! in_array($tab, $allowed, true)) {
            return;
        }

        $this->activeTab = $tab;

        if ($tab === 'support') {
            $this->ensureSupportWelcome();
        }

        if ($tab === 'recommend' && $this->metricsReady && $this->recommendations === []) {
            $this->loadRecommendations();
        }
    }

    public function goToAnalysisStep(int $step): void
    {
        if ($step < 1 || $step > 3) {
            return;
        }

        if ($step > 1 && ! $this->metricsReady) {
            $this->calculateMetrics();
        }

        $this->analysisStep = $step;
    }

    public function nextAnalysisStep(): void
    {
        if ($this->analysisStep === 1) {
            $this->calculateMetrics();
            if ($this->metricsReady) {
                $this->analysisStep = 2;
            }

            return;
        }

        if ($this->analysisStep === 2) {
            $this->generateAiReport();
        }
    }

    public function prevAnalysisStep(): void
    {
        if ($this->analysisStep > 1) {
            $this->analysisStep--;
        }
    }

    public function toggleRestriction(string $key): void
    {
        $allowed = ['gluten_free', 'dairy_free', 'nut_free', 'sugar_free'];
        if (! in_array($key, $allowed, true)) {
            return;
        }

        if (in_array($key, $this->restrictions, true)) {
            $this->restrictions = array_values(array_filter(
                $this->restrictions,
                fn (string $item): bool => $item !== $key
            ));
        } else {
            $this->restrictions[] = $key;
        }
    }

    public function updatedHeightCm(): void
    {
        $this->calculateMetrics();
    }

    public function updatedWeightKg(): void
    {
        $this->calculateMetrics();
    }

    public function updatedAge(): void
    {
        $this->calculateMetrics();
    }

    public function updatedGender(): void
    {
        $this->calculateMetrics();
    }

    public function updatedActivityLevel(): void
    {
        $this->calculateMetrics();
    }

    public function updatedGoal(): void
    {
        $this->calculateMetrics();
    }

    public function calculateMetrics(): void
    {
        $height = max(100, min(250, (int) $this->heightCm));
        $weight = max(30.0, min(300.0, (float) $this->weightKg));
        $age = max(14, min(90, (int) $this->age));

        $this->heightCm = $height;
        $this->weightKg = $weight;
        $this->age = $age;

        $heightM = $height / 100;
        $bmi = $weight / ($heightM * $heightM);
        $this->bmi = round($bmi, 1);
        $this->bmiCategory = match (true) {
            $bmi < 18.5 => 'underweight',
            $bmi < 25 => 'normal',
            $bmi < 30 => 'overweight',
            default => 'obese',
        };

        $bmr = $this->gender === 'female'
            ? (10 * $weight) + (6.25 * $height) - (5 * $age) - 161
            : (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;

        $factor = match ($this->activityLevel) {
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9,
            default => 1.55,
        };

        $tdee = $bmr * $factor;

        $target = match ($this->goal) {
            'lose' => (int) round($tdee * 0.82),
            'gain' => (int) round($tdee * 1.15),
            default => (int) round($tdee),
        };

        $proteinG = round($weight * 1.8, 1);
        $proteinCal = $proteinG * 4;
        $fatCal = $target * 0.25;
        $fatG = round($fatCal / 9, 1);
        $carbsCal = max(0, $target - $proteinCal - $fatCal);
        $carbsG = round($carbsCal / 4, 1);

        $proteinPct = $target > 0 ? (int) round(($proteinCal / $target) * 100) : 0;
        $fatPct = $target > 0 ? (int) round(($fatCal / $target) * 100) : 0;
        $carbsPct = max(0, 100 - $proteinPct - $fatPct);

        $this->bmr = round($bmr);
        $this->tdee = round($tdee);
        $this->targetCalories = $target;
        $this->macroTargets = [
            'protein_g' => $proteinG,
            'carbs_g' => $carbsG,
            'fat_g' => $fatG,
            'protein_pct' => $proteinPct,
            'carbs_pct' => $carbsPct,
            'fat_pct' => $fatPct,
        ];
        $this->metricsReady = true;
        $this->recommendations = [];
        $this->recommendationSource = '';
        $this->aiReport = [];
        $this->reportSource = '';
    }

    public function generateAiReport(): void
    {
        if (! $this->metricsReady) {
            $this->calculateMetrics();
        }

        $this->loadingReport = true;
        $this->aiReport = [];
        $this->reportSource = '';

        $gemini = app(GeminiService::class);
        if ($gemini->isConfigured()) {
            $report = $this->fetchGeminiReport($gemini);
            if ($report !== []) {
                $this->aiReport = $report;
                $this->reportSource = 'gemini';
                $this->analysisStep = 3;
                $this->loadingReport = false;

                return;
            }
        }

        $this->aiReport = $this->localReport();
        $this->reportSource = 'local';
        $this->analysisStep = 3;
        $this->loadingReport = false;
    }

    public function loadRecommendations(): void
    {
        if (! $this->metricsReady || $this->targetCalories === null) {
            $this->calculateMetrics();
        }

        $this->loadingRecommendations = true;
        $this->recommendations = [];
        $this->recommendationSource = '';
        $this->catalogUnavailable = false;

        $meals = $this->catalogMeals();
        if ($meals->isEmpty()) {
            $this->catalogUnavailable = true;
            $this->loadingRecommendations = false;

            return;
        }

        $gemini = app(GeminiService::class);
        if ($gemini->isConfigured()) {
            $aiResults = $this->fetchGeminiRecommendations($gemini, $meals);
            if ($aiResults !== []) {
                if (count($aiResults) < 6) {
                    $existingIds = collect($aiResults)->pluck('meal_id')->all();
                    $fill = collect($this->localRecommendations($meals))
                        ->reject(fn (array $row): bool => in_array((int) ($row['meal_id'] ?? 0), $existingIds, true))
                        ->take(8 - count($aiResults))
                        ->values()
                        ->all();
                    $aiResults = array_merge($aiResults, $fill);
                }

                $this->recommendations = array_slice($aiResults, 0, 8);
                $this->recommendationSource = 'gemini';
                $this->loadingRecommendations = false;

                return;
            }
        }

        $this->recommendations = $this->localRecommendations($meals);
        $this->recommendationSource = 'local';
        $this->loadingRecommendations = false;
    }

    public function askQuickQuestion(string $question): void
    {
        $question = trim($question);
        if ($question === '') {
            return;
        }

        $this->supportInput = $question;
        $this->sendSupportMessage();
    }

    public function sendSupportMessage(): void
    {
        $question = trim($this->supportInput);
        if ($question === '') {
            return;
        }

        $this->supportMessages[] = ['role' => 'user', 'content' => $question];
        $this->supportInput = '';
        $this->loadingSupport = true;
        $this->supportMealPicks = [];
        $this->supportPlanPicks = [];

        $response = $this->buildSupportResponse($question);

        $this->supportMessages[] = ['role' => 'assistant', 'content' => $response['text']];
        $this->supportMealPicks = $response['meals'];
        $this->supportPlanPicks = $response['plans'];
        session([self::SESSION_SUPPORT => $this->supportMessages]);
        $this->loadingSupport = false;
    }

    public function clearSupportChat(): void
    {
        $this->supportMessages = [];
        $this->supportMealPicks = [];
        $this->supportPlanPicks = [];
        session()->forget(self::SESSION_SUPPORT);
        $this->ensureSupportWelcome();
    }

    public function addMealToCart(int $mealId, string $name = '', float $price = 0, string $image = ''): void
    {
        if ($mealId <= 0) {
            return;
        }

        if ($name === '') {
            foreach ($this->recommendations as $rec) {
                if ((int) ($rec['meal_id'] ?? 0) === $mealId) {
                    $name = (string) ($rec['name'] ?? '');
                    $price = (float) ($rec['price'] ?? $price);
                    $image = (string) ($rec['image'] ?? $image);
                    break;
                }
            }
        }

        if ($name === '') {
            $meal = $this->catalogMeals()->firstWhere('id', $mealId);
            if (! is_array($meal)) {
                return;
            }

            $name = (string) ($meal['name'] ?? '');
            $price = (float) ($meal['price'] ?? $price);
            $image = (string) ($meal['image_url'] ?? $image);
        }

        if ($name === '') {
            return;
        }

        $this->dispatch(
            'add-to-cart',
            mealId: $mealId,
            name: $name,
            price: $price,
            image: $image,
        );
    }

    /**
     * @return array<int, string>
     */
    private function defaultQuickQuestions(): array
    {
        $locale = app()->getLocale();

        return $locale === 'ar'
            ? [
                'ما أفضل باقة وجبات تناسبني؟',
                'كيف أبدأ الاشتراك خطوة بخطوة؟',
                'ما الفرق بين المتجر والاشتراك؟',
                'كم رسوم التوصيل والضريبة؟',
                'هل الوجبات مناسبة لخسارة الوزن؟',
            ]
            : [
                'Which meal plan fits me best?',
                'How do I start a subscription step by step?',
                'What is the difference between store and subscription?',
                'What are delivery fees and VAT?',
                'Are meals suitable for weight loss?',
            ];
    }

    private function ensureSupportWelcome(): void
    {
        if ($this->supportMessages !== []) {
            return;
        }

        $locale = app()->getLocale();
        $name = $this->siteName;

        if ($this->metricsReady && $this->geminiConfigured) {
            $gemini = app(GeminiService::class);
            $context = json_encode($this->userProfilePayload(), JSON_UNESCAPED_UNICODE);
            $prompt = $locale === 'ar'
                ? "أنت مستشار تغذية ومبيعات لـ {$name}. رحّب بالعميل بجملتين فقط، اذكر أنك رأيت ملفه الغذائي، واسأله سؤالاً واحداً ذكياً لفهم احتياجه. لا تستخدم markdown."
                : "You are a nutrition and sales advisor for {$name}. Welcome the customer in exactly two sentences, mention you reviewed their nutrition profile, and ask one smart question to understand their need. No markdown.";

            $welcome = $gemini->generate($prompt, [
                ['role' => 'user', 'content' => "Customer profile JSON:\n{$context}"],
            ], false, 0.55);

            if ($welcome !== null && trim($welcome) !== '') {
                $this->supportMessages[] = ['role' => 'assistant', 'content' => trim($welcome)];
                session([self::SESSION_SUPPORT => $this->supportMessages]);

                return;
            }
        }

        $this->supportMessages[] = [
            'role' => 'assistant',
            'content' => (string) __('ai.support_welcome'),
        ];
        session([self::SESSION_SUPPORT => $this->supportMessages]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userProfilePayload(): array
    {
        return [
            'height_cm' => $this->heightCm,
            'weight_kg' => $this->weightKg,
            'age' => $this->age,
            'gender' => $this->gender,
            'activity_level' => $this->activityLevel,
            'goal' => $this->goal,
            'bmi' => $this->bmi,
            'bmi_category' => $this->bmiCategory,
            'bmr' => $this->bmr,
            'tdee' => $this->tdee,
            'target_calories' => $this->targetCalories,
            'macro_targets_g' => $this->macroTargets,
            'diet_style' => $this->dietStyle,
            'restrictions' => $this->restrictions,
            'meals_per_day' => $this->mealsPerDay,
            'health_notes' => $this->healthNotes,
            'report_headline' => $this->aiReport['headline'] ?? null,
        ];
    }

    /**
     * Store catalog meals from the external API (same source as /store).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function catalogMeals(): Collection
    {
        try {
            $meals = app(ExternalDataService::class)->getAllMeals();
        } catch (\Throwable) {
            $meals = [];
        }

        return collect($meals)
            ->filter(fn (mixed $meal): bool => is_array($meal) && (int) ($meal['id'] ?? 0) > 0)
            ->unique(fn (array $meal): int => (int) $meal['id'])
            ->values();
    }

    private function fetchGeminiReport(GeminiService $gemini): array
    {
        $locale = app()->getLocale();
        $lang = $locale === 'ar' ? 'Arabic' : 'English';

        $system = <<<PROMPT
You are a senior nutrition consultant and sales strategist for Diet Watchers (Saudi Arabia healthy meal delivery).
Analyze the customer profile and return ONLY valid JSON with these keys (all string values in {$lang}):
- headline (short motivating title)
- summary (2-3 sentences personalized overview)
- bmi_comment (BMI interpretation for this person)
- calorie_strategy (how target calories support their goal)
- macro_advice (protein/carbs/fat guidance)
- meal_timing (how to split meals across the day)
- lifestyle_tips (array of 3-4 actionable tips as strings)
- recommended_plan_type (one of: subscription, store, both)
- plan_pitch (persuasive but honest recommendation like a top salesperson)
- weekly_focus (one clear focus for the next 7 days)
- caution (optional health caution if health_notes mention conditions, else empty string)
Be warm, expert, and specific. Use numbers from the profile.
PROMPT;

        $user = json_encode($this->userProfilePayload(), JSON_UNESCAPED_UNICODE);

        $raw = $gemini->generate($system, [
            ['role' => 'user', 'content' => $user],
        ], true, 0.5);

        if ($raw === null) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $decoded = json_decode($this->extractJsonObject($raw), true);
        }

        if (! is_array($decoded) || ! isset($decoded['headline'])) {
            return [];
        }

        $tips = $decoded['lifestyle_tips'] ?? [];
        if (! is_array($tips)) {
            $tips = [];
        }

        return [
            'headline' => (string) ($decoded['headline'] ?? ''),
            'summary' => (string) ($decoded['summary'] ?? ''),
            'bmi_comment' => (string) ($decoded['bmi_comment'] ?? ''),
            'calorie_strategy' => (string) ($decoded['calorie_strategy'] ?? ''),
            'macro_advice' => (string) ($decoded['macro_advice'] ?? ''),
            'meal_timing' => (string) ($decoded['meal_timing'] ?? ''),
            'lifestyle_tips' => array_values(array_filter(array_map('strval', $tips))),
            'recommended_plan_type' => (string) ($decoded['recommended_plan_type'] ?? 'both'),
            'plan_pitch' => (string) ($decoded['plan_pitch'] ?? ''),
            'weekly_focus' => (string) ($decoded['weekly_focus'] ?? ''),
            'caution' => (string) ($decoded['caution'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function localReport(): array
    {
        $locale = app()->getLocale();
        $bmiLabel = (string) __('ai.bmi_'.$this->bmiCategory);
        $goalLabel = (string) __('ai.goal_'.$this->goal);

        if ($locale === 'ar') {
            return [
                'headline' => 'تقريرك الغذائي الشخصي',
                'summary' => sprintf(
                    'بناءً على بياناتك (%.1f كجم، %d سم، %d سنة)، هدفك هو %s وسعراتك المستهدفة %d سعرة يومياً.',
                    $this->weightKg,
                    $this->heightCm,
                    $this->age,
                    $goalLabel,
                    (int) $this->targetCalories
                ),
                'bmi_comment' => sprintf('مؤشر كتلة الجسم %.1f — تصنيف: %s.', (float) $this->bmi, $bmiLabel),
                'calorie_strategy' => sprintf('TDEE التقريبي %d سعرة. للوصول لهدف %s، نوصي بـ %d سعرة يومياً.', (int) $this->tdee, $goalLabel, (int) $this->targetCalories),
                'macro_advice' => sprintf('بروتين %.0fغ، كارب %.0fغ، دهون %.0fغ يومياً.', $this->macroTargets['protein_g'], $this->macroTargets['carbs_g'], $this->macroTargets['fat_g']),
                'meal_timing' => sprintf('وزّع %d وجبات على اليوم مع بروتين في كل وجبة.', $this->mealsPerDay),
                'lifestyle_tips' => [
                    'اشرب 2-3 لتر ماء يومياً.',
                    'نام 7-8 ساعات لتحسين التمثيل الغذائي.',
                    'سجّل وجباتك لمدة أسبوع لمتابعة التقدم.',
                ],
                'recommended_plan_type' => $this->goal === 'lose' ? 'subscription' : 'both',
                'plan_pitch' => 'باقة الاشتراك تناسبك لأنها توفر وجبات محسوبة يومياً بدون عناء التخطيط.',
                'weekly_focus' => 'التزم بالسعرات المستهدفة 5 أيام من 7 مع وجبة مرنة في نهاية الأسبوع.',
                'caution' => $this->healthNotes !== '' ? 'استشر مختصاً إذا لديك حالة صحية مزمنة قبل تغيير نظامك الغذائي.' : '',
            ];
        }

        return [
            'headline' => 'Your personal nutrition report',
            'summary' => sprintf(
                'Based on your stats (%.1f kg, %d cm, age %d), your goal is %s with a %d kcal daily target.',
                $this->weightKg,
                $this->heightCm,
                $this->age,
                $goalLabel,
                (int) $this->targetCalories
            ),
            'bmi_comment' => sprintf('BMI %.1f — category: %s.', (float) $this->bmi, $bmiLabel),
            'calorie_strategy' => sprintf('Estimated TDEE %d kcal. To support %s, we recommend %d kcal/day.', (int) $this->tdee, $goalLabel, (int) $this->targetCalories),
            'macro_advice' => sprintf('Daily macros: %.0fg protein, %.0fg carbs, %.0fg fat.', $this->macroTargets['protein_g'], $this->macroTargets['carbs_g'], $this->macroTargets['fat_g']),
            'meal_timing' => sprintf('Split intake across %d meals with protein in each meal.', $this->mealsPerDay),
            'lifestyle_tips' => [
                'Drink 2-3 liters of water daily.',
                'Sleep 7-8 hours to support metabolism.',
                'Track meals for one week to measure progress.',
            ],
            'recommended_plan_type' => $this->goal === 'lose' ? 'subscription' : 'both',
            'plan_pitch' => 'A meal subscription fits you best because it delivers calculated meals daily without planning stress.',
            'weekly_focus' => 'Hit your calorie target 5 of 7 days with one flexible meal on the weekend.',
            'caution' => $this->healthNotes !== '' ? 'Consult a professional if you have a chronic condition before changing your diet.' : '',
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $meals
     * @return array<int, array<string, mixed>>
     */
    private function fetchGeminiRecommendations(GeminiService $gemini, Collection $meals): array
    {
        $locale = app()->getLocale();
        $lang = $locale === 'ar' ? 'Arabic' : 'English';
        $mealsPerDay = max(1, $this->mealsPerDay);
        $perMealCal = (int) round(max(120, (float) ($this->targetCalories ?? 0) / $mealsPerDay));

        $catalog = $this->rankMealsCollection($meals)
            ->take(100)
            ->map(fn (array $meal): array => [
                'id' => (int) ($meal['id'] ?? 0),
                'name' => (string) ($meal['name'] ?? ''),
                'calories' => (int) ($meal['calories'] ?? 0),
                'protein' => (float) ($meal['protein'] ?? 0),
                'carbs' => (float) ($meal['carbs'] ?? 0),
                'fat' => (float) ($meal['fat'] ?? 0),
                'price' => (float) ($meal['price'] ?? 0),
                'group_name' => (string) ($meal['group_name'] ?? $meal['category_name'] ?? ''),
                'tags' => collect($meal['tags'] ?? [])
                    ->map(fn ($tag) => is_array($tag)
                        ? (string) ($tag['display_name'] ?? $tag['name'] ?? '')
                        : '')
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $system = <<<PROMPT
You are a senior nutrition consultant and meal sales advisor for Diet Watchers in Saudi Arabia.
Return ONLY valid JSON: an array of exactly 8 objects with keys:
- meal_id (int, must exist in catalog)
- reason_ar (string, 1-2 sentences in Arabic — specific to goal, macros, restrictions)
- reason_en (string, 1-2 sentences in English)
- fit_score (int 0-100)
- upsell_tip (string in {$lang} — one persuasive sentence to buy today)

Rules:
- Pick ONLY from catalog meal_id values. Never invent meals.
- Respect restrictions and diet_style from the profile (exclude incompatible meals).
- Prioritize meals whose calories are within ±25% of per_meal_calorie_target.
- For weight loss (goal=lose): favor lower calories and higher protein.
- For muscle gain (goal=gain): favor higher protein and adequate calories.
- Reasons must mention concrete numbers (calories, protein) and the customer's goal.
PROMPT;

        $user = json_encode([
            'profile' => array_merge($this->userProfilePayload(), [
                'per_meal_calorie_target' => $perMealCal,
            ]),
            'catalog' => $catalog,
        ], JSON_UNESCAPED_UNICODE);

        $raw = $gemini->generate($system, [
            ['role' => 'user', 'content' => $user],
        ], true, 0.5);

        if ($raw === null) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $decoded = json_decode($this->extractJsonArray($raw), true);
        }

        if (! is_array($decoded)) {
            return [];
        }

        $rows = array_is_list($decoded) ? $decoded : ($decoded['recommendations'] ?? $decoded['meals'] ?? []);
        if (! is_array($rows)) {
            return [];
        }

        $mealMap = $meals->keyBy(fn (array $meal): int => (int) ($meal['id'] ?? 0));

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) use ($mealMap, $locale): ?array {
                $mealId = (int) ($row['meal_id'] ?? 0);
                $meal = $mealMap->get($mealId);
                if (! is_array($meal)) {
                    return null;
                }

                $reason = $locale === 'ar'
                    ? (string) ($row['reason_ar'] ?? $row['reason_en'] ?? '')
                    : (string) ($row['reason_en'] ?? $row['reason_ar'] ?? '');

                $upsell = (string) ($row['upsell_tip'] ?? '');
                if ($upsell !== '') {
                    $reason = trim($reason.' '.$upsell);
                }

                return $this->formatRecommendationCard($meal, $reason, (int) ($row['fit_score'] ?? 0));
            })
            ->filter()
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $meals
     * @return array<int, array<string, mixed>>
     */
    private function localRecommendations(Collection $meals): array
    {
        $locale = app()->getLocale();
        $mealsPerDay = max(1, $this->mealsPerDay);
        $targetCal = (float) ($this->targetCalories ?? 0);
        $perMealCal = max(120, $targetCal / $mealsPerDay);

        $ranked = $this->rankMealsWithScores($meals)->take(8);

        if ($ranked->isEmpty()) {
            $ranked = $meals->take(8)->map(fn (array $meal): array => ['meal' => $meal, 'distance' => 0.5]);
        }

        return $ranked
            ->map(function (array $row) use ($locale, $perMealCal): array {
                $meal = $row['meal'];
                $fit = (int) max(58, min(98, round(100 - ($row['distance'] * 55))));
                $mealCal = (int) ($meal['calories'] ?? 0);

                $reason = $locale === 'ar'
                    ? ($mealCal > 0
                        ? sprintf('وجبة من متجرنا بـ %d سعرة — قريبة من هدفك لكل وجبة (~%d سعرة).', $mealCal, (int) round($perMealCal))
                        : 'وجبة من متجرنا تناسب أهدافك الغذائية — اضغط لعرض التفاصيل.')
                    : ($mealCal > 0
                        ? sprintf('Store meal at %d kcal — close to your per-meal target (~%d kcal).', $mealCal, (int) round($perMealCal))
                        : 'A store meal that fits your nutrition goals — tap to view details.');

                return $this->formatRecommendationCard($meal, $reason, $fit);
            })
            ->values()
            ->all();
    }

    /**
     * Rank catalog meals by macro/calorie fit for the current profile.
     *
     * @param  Collection<int, array<string, mixed>>  $meals
     * @return Collection<int, array{meal: array<string, mixed>, distance: float}>
     */
    private function rankMealsWithScores(Collection $meals): Collection
    {
        $mealsPerDay = max(1, $this->mealsPerDay);
        $targetCal = (float) ($this->targetCalories ?? 0);
        $perMealCal = max(120, $targetCal / $mealsPerDay);
        $perMealProtein = max(5, (float) ($this->macroTargets['protein_g'] ?? 0) / $mealsPerDay);
        $perMealCarbs = max(5, (float) ($this->macroTargets['carbs_g'] ?? 0) / $mealsPerDay);
        $perMealFat = max(2, (float) ($this->macroTargets['fat_g'] ?? 0) / $mealsPerDay);

        return $meals
            ->map(function (array $meal) use ($perMealCal, $perMealProtein, $perMealCarbs, $perMealFat): array {
                $cal = (int) ($meal['calories'] ?? 0);
                $protein = (float) ($meal['protein'] ?? 0);
                $carbs = (float) ($meal['carbs'] ?? 0);
                $fat = (float) ($meal['fat'] ?? 0);

                if ($cal <= 0) {
                    return ['meal' => $meal, 'distance' => 0.75];
                }

                $score = (
                    abs($cal - $perMealCal) / $perMealCal * 0.5
                    + abs($protein - $perMealProtein) / $perMealProtein * 0.25
                    + abs($carbs - $perMealCarbs) / max($perMealCarbs, 1) * 0.15
                    + abs($fat - $perMealFat) / max($perMealFat, 1) * 0.1
                );

                if ($this->goal === 'lose' && $cal > $perMealCal * 1.35) {
                    $score += 0.35;
                }
                if ($this->goal === 'gain' && $protein < $perMealProtein * 0.6) {
                    $score += 0.2;
                }

                return ['meal' => $meal, 'distance' => $score];
            })
            ->sortBy('distance')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $meals
     * @return Collection<int, array<string, mixed>>
     */
    private function rankMealsCollection(Collection $meals): Collection
    {
        return $this->rankMealsWithScores($meals)->pluck('meal')->values();
    }

    /**
     * @param  array<string, mixed>  $meal
     * @return array<string, mixed>
     */
    private function formatRecommendationCard(array $meal, string $reason, int $fitScore): array
    {
        $mealId = (int) ($meal['id'] ?? 0);
        $price = (float) ($meal['price'] ?? 0);
        $offerPrice = (float) ($meal['offer_price'] ?? 0);

        return [
            'meal_id' => $mealId,
            'name' => (string) ($meal['name'] ?? ''),
            'calories' => (int) ($meal['calories'] ?? 0),
            'protein' => (float) ($meal['protein'] ?? 0),
            'carbs' => (float) ($meal['carbs'] ?? 0),
            'fat' => (float) ($meal['fat'] ?? 0),
            'price' => $offerPrice > 0 && $offerPrice < $price ? $offerPrice : $price,
            'image' => (string) ($meal['image_url'] ?? ''),
            'url' => $mealId > 0 ? route('store.show', $mealId) : '',
            'reason' => $reason,
            'fit_score' => max(0, min(100, $fitScore)),
        ];
    }

    private function buildAdvisorSystemPrompt(): string
    {
        $locale = app()->getLocale();
        $siteName = (string) Setting::getValue('site_name', config('app.name'));
        $footer = (string) Setting::getValue('footer_description_'.$locale, '');
        $deliveryFee = (string) Setting::getValue('delivery_fee', '25');
        $vat = (string) Setting::getValue('vat_rate', '15');

        $faqBlock = Faq::query()
            ->where('is_active', true)
            ->orderBy('order_column')
            ->limit(25)
            ->get()
            ->map(function (Faq $faq) use ($locale): string {
                $q = (string) ($faq->translate($locale)?->question ?? '');
                $a = (string) ($faq->translate($locale)?->answer ?? '');
                if ($q === '' || $a === '') {
                    return '';
                }

                return "Q: {$q}\nA: {$a}";
            })
            ->filter()
            ->implode("\n\n");

        $profileJson = json_encode($this->userProfilePayload(), JSON_UNESCAPED_UNICODE);
        $catalogJson = json_encode($this->catalogSummaryForPrompt(), JSON_UNESCAPED_UNICODE);
        $storeUrl = route('store.index');
        $plansUrl = route('meal-plans.index');
        $checkoutUrl = route('checkout.index');

        $langInstruction = $locale === 'ar'
            ? 'رد بالعربية ما لم يكتب العميل بالإنجليزية. كن ودوداً ومحترفاً مثل أفضل مستشار مبيعات. اذكر أسماء وجبات أو باقات محددة من الكتالوج عند التوصية.'
            : 'Reply in English unless the user writes in Arabic. Be warm and professional like a top sales advisor. Name specific meals or plans from the catalog when recommending.';

        return <<<PROMPT
You are the AI nutrition consultant and customer success advisor for {$siteName}, a premium healthy meal subscription and store in Saudi Arabia.
Your job: answer ANY customer question — plans, store meals, delivery, pricing, weight loss, subscription steps, or general nutrition.
Recommend specific meals or subscription plans from the catalog below when relevant. Mention real product names.
Use the customer profile when available. Ask one short follow-up only when critical info is missing.
Keep answers helpful and concise (3-8 sentences) unless the user asks for detail.
{$langInstruction}

Customer profile:
{$profileJson}

Store catalog (meals + subscription plans):
{$catalogJson}

Useful links: Store {$storeUrl} | Meal plans {$plansUrl} | Checkout {$checkoutUrl}
Company context: {$footer}
Delivery fee (SAR): {$deliveryFee}. VAT: {$vat}%.

FAQ knowledge base:
{$faqBlock}
PROMPT;
    }

    /**
     * @return array{text: string, meals: array<int, array<string, mixed>>, plans: array<int, array<string, mixed>>}
     */
    private function buildSupportResponse(string $question): array
    {
        $intent = $this->detectSupportIntent($question);
        $meals = $this->topMealPicksForUser(4);
        $plans = $this->rankedPlansForUser(3);

        $gemini = app(GeminiService::class);
        if ($gemini->isConfigured()) {
            $answer = $gemini->generate(
                $this->buildAdvisorSystemPrompt(),
                $this->supportMessages,
                false,
                0.65
            );

            if ($answer !== null && trim($answer) !== '') {
                return [
                    'text' => trim($answer),
                    'meals' => in_array($intent, ['meals', 'plan', 'weight_loss', 'general'], true) ? $meals : [],
                    'plans' => in_array($intent, ['plan', 'subscription', 'weight_loss', 'store_diff', 'general'], true) ? $plans : [],
                ];
            }
        }

        return $this->localSupportAnswer($question, $intent, $meals, $plans);
    }

    /**
     * @param  array<int, array<string, mixed>>  $meals
     * @param  array<int, array<string, mixed>>  $plans
     * @return array{text: string, meals: array<int, array<string, mixed>>, plans: array<int, array<string, mixed>>}
     */
    private function localSupportAnswer(string $question, string $intent, array $meals, array $plans): array
    {
        $locale = app()->getLocale();
        $faqAnswer = $this->matchFaqAnswer($question);
        if ($faqAnswer !== null) {
            return [
                'text' => $faqAnswer,
                'meals' => in_array($intent, ['meals', 'plan', 'weight_loss'], true) ? $meals : [],
                'plans' => in_array($intent, ['plan', 'subscription', 'weight_loss'], true) ? $plans : [],
            ];
        }

        $siteName = $this->siteName;
        $targetCal = (int) ($this->targetCalories ?? 0);
        $goalLabel = (string) __('ai.goal_'.$this->goal);
        $plansUrl = route('meal-plans.index');
        $storeUrl = route('store.index');
        $checkoutUrl = route('checkout.index');
        $deliveryFee = (string) Setting::getValue('delivery_fee', '25');
        $vat = (string) Setting::getValue('vat_rate', '15');

        $text = match ($intent) {
            'plan' => $this->buildPlanAdviceText($locale, $siteName, $targetCal, $goalLabel, $plans, $plansUrl, $checkoutUrl),
            'subscription' => $locale === 'ar'
                ? "لبدء الاشتراك مع {$siteName}:\n1) اختر باقة من صفحة الباقات: {$plansUrl}\n2) حدّد السعرات والمدة المناسبة لك\n3) أكمل الدفع من: {$checkoutUrl}\n\n".($targetCal > 0 ? "بناءً على تحليلك، هدفك حوالي {$targetCal} سعرة يومياً — اختر باقة قريبة من هذا الرقم." : 'أكمل التحليل الذكي في التبويب الأول لأحدد لك السعرات المناسبة.')
                : "To start your {$siteName} subscription:\n1) Pick a plan: {$plansUrl}\n2) Choose calories and duration\n3) Complete checkout: {$checkoutUrl}\n\n".($targetCal > 0 ? "Based on your analysis, your target is about {$targetCal} kcal/day." : 'Complete the smart analysis tab for a calorie target.'),
            'store_diff' => $locale === 'ar'
                ? "الفرق باختصار:\n• الاشتراك ({$plansUrl}): وجبات يومية محسوبة حسب سعراتك وهدفك — مثالي للالتزام طويل المدى.\n• المتجر ({$storeUrl}): طلب وجبات منفردة بدون اشتراك — مثالي للتجربة أو الطلبات العرضية.\n\n".($this->goal === 'lose' ? 'لخسارة الوزن أنصح بالاشتراك لضبط السعرات يومياً.' : 'يمكنك الجمع بينهما حسب أسلوب حياتك.')
                : "Quick difference:\n• Subscription ({$plansUrl}): daily calculated meals for your goal.\n• Store ({$storeUrl}): one-off meals without a plan.\n\n".($this->goal === 'lose' ? 'For weight loss, subscription helps you stay on calories daily.' : 'You can use both depending on your lifestyle.'),
            'delivery' => $locale === 'ar'
                ? "رسوم التوصيل تقريباً {$deliveryFee} ريال، والضريبة {$vat}% تُضاف حسب النظام السعودي. التفاصيل الدقيقة تظهر عند إتمام الطلب في صفحة الدفع: {$checkoutUrl}"
                : "Delivery is about {$deliveryFee} SAR; VAT is {$vat}% per Saudi regulations. Exact totals appear at checkout: {$checkoutUrl}",
            'weight_loss' => $this->buildWeightLossAdviceText($locale, $targetCal, $goalLabel, $plans, $meals, $plansUrl, $storeUrl),
            'meals' => $this->buildMealAdviceText($locale, $meals, $storeUrl, $targetCal),
            default => $this->buildGeneralAdviceText($locale, $siteName, $question, $meals, $plans, $storeUrl, $plansUrl),
        };

        return [
            'text' => $text,
            'meals' => in_array($intent, ['meals', 'plan', 'weight_loss', 'general'], true) ? $meals : [],
            'plans' => in_array($intent, ['plan', 'subscription', 'weight_loss', 'store_diff', 'general'], true) ? $plans : [],
        ];
    }

    private function detectSupportIntent(string $question): string
    {
        $q = mb_strtolower(trim($question));

        $patterns = [
            'plan' => ['باقة', 'باقات', 'برنامج', 'خطة', 'plan', 'package', 'program', 'تناسبني', 'fits me', 'best plan'],
            'subscription' => ['اشتراك', 'أبدأ', 'ابدأ', 'خطوة', 'كيف', 'subscribe', 'start', 'step', 'sign up'],
            'store_diff' => ['فرق', 'متجر', 'difference', 'store vs', 'اشتراك والمتجر'],
            'delivery' => ['توصيل', 'ضريبة', 'رسوم', 'delivery', 'vat', 'tax', 'fee'],
            'weight_loss' => ['خسارة', 'تنحيف', 'وزن', 'رجيم', 'lose weight', 'weight loss', 'fat loss', 'دايت'],
            'meals' => ['وجبة', 'وجبات', 'اقتراح', 'meal', 'suggest', 'menu', 'أكل'],
        ];

        $scores = [];
        foreach ($patterns as $intent => $keywords) {
            $scores[$intent] = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($q, mb_strtolower($keyword))) {
                    $scores[$intent]++;
                }
            }
        }

        arsort($scores);
        $top = array_key_first($scores);

        return ($top !== null && $scores[$top] > 0) ? $top : 'general';
    }

    private function matchFaqAnswer(string $question): ?string
    {
        $locale = app()->getLocale();
        $needle = mb_strtolower(trim($question));
        $bestScore = 0;
        $bestAnswer = null;

        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('order_column')
            ->limit(40)
            ->get();

        foreach ($faqs as $faq) {
            $q = mb_strtolower((string) ($faq->translate($locale)?->question ?? ''));
            if ($q === '') {
                continue;
            }

            $score = 0;
            if ($needle === $q) {
                $score = 100;
            } elseif (str_contains($needle, $q) || str_contains($q, $needle)) {
                $score = 80;
            } else {
                foreach (preg_split('/\s+/u', $q) as $word) {
                    if (mb_strlen($word) >= 3 && str_contains($needle, $word)) {
                        $score += 10;
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAnswer = strip_tags((string) ($faq->translate($locale)?->answer ?? ''));
            }
        }

        return $bestScore >= 20 ? $bestAnswer : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $plans
     */
    private function buildPlanAdviceText(
        string $locale,
        string $siteName,
        int $targetCal,
        string $goalLabel,
        array $plans,
        string $plansUrl,
        string $checkoutUrl
    ): string {
        if ($plans === []) {
            return $locale === 'ar'
                ? "تصفّح باقات {$siteName} من هنا: {$plansUrl} — وأكمل التحليل الذكي لأحدد السعرات الأنسب لك."
                : "Browse {$siteName} plans here: {$plansUrl} — complete the smart analysis for a calorie match.";
        }

        $lines = $locale === 'ar'
            ? ["بناءً على هدفك ({$goalLabel})".($targetCal > 0 ? " وسعراتك المستهدفة (~{$targetCal})" : '').", هذه أقرب الباقات لك من {$siteName}:"]
            : ["Based on your goal ({$goalLabel})".($targetCal > 0 ? " and ~{$targetCal} kcal target" : '').", these plans fit best:"];

        foreach ($plans as $plan) {
            $name = (string) ($plan['name'] ?? '');
            $cal = (int) ($plan['calories_per_day'] ?? 0);
            $price = (int) ($plan['min_price'] ?? $plan['price'] ?? 0);
            $url = (string) ($plan['url'] ?? '');
            $lines[] = $locale === 'ar'
                ? "• {$name}".($cal > 0 ? " — ~{$cal} سعرة/يوم" : '').($price > 0 ? " — يبدأ من {$price} ر.س" : '').($url !== '' ? "\n  {$url}" : '')
                : "• {$name}".($cal > 0 ? " — ~{$cal} kcal/day" : '').($price > 0 ? " — from {$price} SAR" : '').($url !== '' ? "\n  {$url}" : '');
        }

        $lines[] = $locale === 'ar'
            ? "للاشتراك: {$checkoutUrl}"
            : "Subscribe: {$checkoutUrl}";

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $plans
     * @param  array<int, array<string, mixed>>  $meals
     */
    private function buildWeightLossAdviceText(
        string $locale,
        int $targetCal,
        string $goalLabel,
        array $plans,
        array $meals,
        string $plansUrl,
        string $storeUrl
    ): string {
        $intro = $locale === 'ar'
            ? 'نعم — وجباتنا مصممة لدعم خسارة الوزن عبر ضبط السعرات والبروتين.'.($targetCal > 0 ? " هدفك الحالي ~{$targetCal} سعرة يومياً." : ' أكمل التحليل الذكي لحساب سعراتك.')
            : 'Yes — our meals support weight loss through calorie and protein control.'.($targetCal > 0 ? " Your current target is ~{$targetCal} kcal/day." : ' Complete the analysis for your calorie target.');

        $parts = [$intro];

        if ($plans !== []) {
            $parts[] = $locale === 'ar' ? 'أقرب الباقات:' : 'Closest plans:';
            foreach (array_slice($plans, 0, 2) as $plan) {
                $parts[] = '• '.($plan['name'] ?? '').' — '.($plan['url'] ?? $plansUrl);
            }
        }

        if ($meals !== []) {
            $parts[] = $locale === 'ar' ? 'وجبات من المتجر تناسبك:' : 'Store picks for you:';
            foreach (array_slice($meals, 0, 2) as $meal) {
                $parts[] = '• '.($meal['name'] ?? '').' — '.($meal['url'] ?? $storeUrl);
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $meals
     */
    private function buildMealAdviceText(string $locale, array $meals, string $storeUrl, int $targetCal): string
    {
        if ($meals === []) {
            return $locale === 'ar'
                ? "تصفّح متجر الوجبات: {$storeUrl}"
                : "Browse our meal store: {$storeUrl}";
        }

        $lines = $locale === 'ar'
            ? ['هذه وجبات من متجرنا قريبة من احتياجك'.($targetCal > 0 ? " (~{$targetCal} سعرة/يوم)" : '').':']
            : ['Store meals that match your needs'.($targetCal > 0 ? " (~{$targetCal} kcal/day)" : '').':'];

        foreach ($meals as $meal) {
            $cal = (int) ($meal['calories'] ?? 0);
            $lines[] = $locale === 'ar'
                ? '• '.($meal['name'] ?? '').($cal > 0 ? " ({$cal} سعرة)" : '').' — '.($meal['url'] ?? $storeUrl)
                : '• '.($meal['name'] ?? '').($cal > 0 ? " ({$cal} kcal)" : '').' — '.($meal['url'] ?? $storeUrl);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $meals
     * @param  array<int, array<string, mixed>>  $plans
     */
    private function buildGeneralAdviceText(
        string $locale,
        string $siteName,
        string $question,
        array $meals,
        array $plans,
        string $storeUrl,
        string $plansUrl
    ): string {
        if (! $this->metricsReady) {
            $this->calculateMetrics();
        }

        $targetCal = (int) ($this->targetCalories ?? 0);
        $intro = $locale === 'ar'
            ? "سؤالك: «{$question}»\n\nأنا مستشار {$siteName} — أساعدك في الباقات، الوجبات، التوصيل، والتغذية."
            : "Your question: \"{$question}\"\n\nI'm the {$siteName} advisor — I can help with plans, meals, delivery, and nutrition.";

        $parts = [$intro];

        if ($targetCal > 0) {
            $parts[] = $locale === 'ar'
                ? "من تحليلك: هدفك ~{$targetCal} سعرة يومياً."
                : "From your profile: ~{$targetCal} kcal/day target.";
        }

        if ($plans !== []) {
            $parts[] = $locale === 'ar' ? 'باقة مقترحة: '.($plans[0]['name'] ?? '').' — '.($plans[0]['url'] ?? $plansUrl) : 'Suggested plan: '.($plans[0]['name'] ?? '').' — '.($plans[0]['url'] ?? $plansUrl);
        }

        if ($meals !== []) {
            $parts[] = $locale === 'ar' ? 'وجبة مقترحة: '.($meals[0]['name'] ?? '').' — '.($meals[0]['url'] ?? $storeUrl) : 'Suggested meal: '.($meals[0]['name'] ?? '').' — '.($meals[0]['url'] ?? $storeUrl);
        }

        $parts[] = $locale === 'ar'
            ? "للمزيد: الباقات {$plansUrl} | المتجر {$storeUrl} | أو أكمل التحليل الذكي لتقرير أدق."
            : "More: plans {$plansUrl} | store {$storeUrl} | or complete smart analysis for a detailed report.";

        return implode("\n\n", $parts);
    }

    /**
     * @return array{meals: array<int, array<string, mixed>>, plans: array<int, array<string, mixed>>}
     */
    private function catalogSummaryForPrompt(): array
    {
        $meals = $this->catalogMeals()
            ->when($this->metricsReady, fn (Collection $c) => $this->rankMealsCollection($c))
            ->take(40)
            ->map(fn (array $meal): array => [
                'id' => (int) ($meal['id'] ?? 0),
                'name' => (string) ($meal['name'] ?? ''),
                'calories' => (int) ($meal['calories'] ?? 0),
                'protein' => (float) ($meal['protein'] ?? 0),
                'group_name' => (string) ($meal['group_name'] ?? ''),
                'price' => (float) ($meal['price'] ?? 0),
            ])
            ->values()
            ->all();

        $plans = collect($this->catalogPlans())
            ->take(12)
            ->map(fn (array $plan): array => [
                'id' => (int) ($plan['id'] ?? 0),
                'name' => (string) ($plan['name'] ?? ''),
                'calories_per_day' => (int) ($plan['calories_per_day'] ?? 0),
                'min_price' => (int) ($plan['min_price'] ?? 0),
            ])
            ->values()
            ->all();

        return ['meals' => $meals, 'plans' => $plans];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogPlans(): array
    {
        try {
            return app(ExternalDataService::class)->getPrograms();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rankedPlansForUser(int $limit = 3): array
    {
        $targetCal = (int) ($this->targetCalories ?? 0);
        if ($targetCal <= 0 && $this->metricsReady) {
            $targetCal = (int) ($this->tdee ?? 0);
        }

        return collect($this->catalogPlans())
            ->filter(fn (array $plan): bool => (int) ($plan['id'] ?? 0) > 0)
            ->map(function (array $plan) use ($targetCal): array {
                $cal = (int) ($plan['calories_per_day'] ?? 0);
                $distance = $targetCal > 0 && $cal > 0 ? abs($cal - $targetCal) / $targetCal : 0.5;

                return [
                    'plan' => $plan,
                    'distance' => $distance,
                ];
            })
            ->sortBy('distance')
            ->take($limit)
            ->map(function (array $row): array {
                $plan = $row['plan'];
                $id = (int) ($plan['id'] ?? 0);

                return [
                    'id' => $id,
                    'name' => (string) ($plan['name'] ?? ''),
                    'calories_per_day' => (int) ($plan['calories_per_day'] ?? 0),
                    'min_price' => (int) ($plan['min_price'] ?? $plan['price'] ?? 0),
                    'image' => (string) ($plan['image_url'] ?? ''),
                    'url' => $id > 0 ? route('meal-plans.show', $id) : '',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topMealPicksForUser(int $limit = 4): array
    {
        $meals = $this->catalogMeals();
        if ($meals->isEmpty()) {
            return [];
        }

        if (! $this->metricsReady) {
            $this->calculateMetrics();
        }

        return array_slice($this->localRecommendations($meals), 0, $limit);
    }

    private function loadAboutContent(): void
    {
        $locale = app()->getLocale();
        $why = WhyChooseSection::query()->where('is_active', true)->first();

        $parts = array_filter([
            (string) Setting::getValue('footer_description_'.$locale, ''),
            $why ? (string) $why->subtitle($locale) : '',
            $why ? (string) $why->title($locale) : '',
        ]);

        $this->aboutDescription = implode("\n\n", $parts);

        $this->aboutContact = implode(' · ', array_filter([
            (string) Setting::getValue('contact_phone', ''),
            (string) Setting::getValue('contact_email', ''),
        ]));

        $this->aboutFaqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('order_column')
            ->limit(6)
            ->get()
            ->map(fn (Faq $faq) => [
                'question' => (string) ($faq->translate($locale)?->question ?? ''),
                'answer' => strip_tags((string) ($faq->translate($locale)?->answer ?? '')),
            ])
            ->filter(fn (array $row) => $row['question'] !== '' && $row['answer'] !== '')
            ->values()
            ->all();
    }

    private function extractJsonArray(string $raw): string
    {
        $start = strpos($raw, '[');
        $end = strrpos($raw, ']');
        if ($start === false || $end === false || $end <= $start) {
            return $raw;
        }

        return substr($raw, $start, $end - $start + 1);
    }

    private function extractJsonObject(string $raw): string
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) {
            return $raw;
        }

        return substr($raw, $start, $end - $start + 1);
    }

    public function render()
    {
        return view('livewire.ai-assistant.meal-assistant');
    }
}
