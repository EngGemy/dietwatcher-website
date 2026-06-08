<?php

declare(strict_types=1);

namespace App\Livewire\AiAssistant;

use App\Models\Faq;
use App\Models\Meal;
use App\Models\Settings\Setting;
use App\Models\WhyChooseSection;
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

    public string $supportInput = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $supportMessages = [];

    public bool $loadingSupport = false;

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

        $meals = $this->activeMeals();
        if ($meals->isEmpty()) {
            $this->loadingRecommendations = false;

            return;
        }

        $gemini = app(GeminiService::class);
        if ($gemini->isConfigured()) {
            $aiResults = $this->fetchGeminiRecommendations($gemini, $meals);
            if ($aiResults !== []) {
                $this->recommendations = $aiResults;
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

        $gemini = app(GeminiService::class);
        $answer = null;

        if ($gemini->isConfigured()) {
            $answer = $gemini->generate(
                $this->buildAdvisorSystemPrompt(),
                $this->supportMessages,
                false,
                0.65
            );
        }

        if ($answer === null) {
            $answer = $this->fallbackSupportAnswer($question);
        }

        $this->supportMessages[] = ['role' => 'assistant', 'content' => $answer];
        session([self::SESSION_SUPPORT => $this->supportMessages]);
        $this->loadingSupport = false;
    }

    public function clearSupportChat(): void
    {
        $this->supportMessages = [];
        session()->forget(self::SESSION_SUPPORT);
        $this->ensureSupportWelcome();
    }

    public function addMealToCart(int $mealId): void
    {
        if ($mealId <= 0) {
            return;
        }

        $meal = Meal::query()->active()->storeProduct()->find($mealId);
        if ($meal === null) {
            return;
        }

        $locale = app()->getLocale();
        $name = (string) ($meal->translate($locale)?->name ?? $meal->name ?? '');
        $image = (string) ($meal->cover_image ?? '');

        $this->dispatch(
            'add-to-cart',
            mealId: $mealId,
            name: $name,
            price: (float) $meal->price,
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
     * @return Collection<int, Meal>
     */
    private function activeMeals(): Collection
    {
        return Meal::query()
            ->active()
            ->storeProduct()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->limit(60)
            ->get();
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
     * @param  Collection<int, Meal>  $meals
     * @return array<int, array<string, mixed>>
     */
    private function fetchGeminiRecommendations(GeminiService $gemini, Collection $meals): array
    {
        $locale = app()->getLocale();
        $catalog = $meals->map(fn (Meal $meal) => [
            'id' => $meal->id,
            'name' => (string) ($meal->translate($locale)?->name ?? $meal->name),
            'calories' => (int) $meal->calories,
            'protein' => (float) $meal->protein,
            'carbs' => (float) $meal->carbs,
            'fat' => (float) $meal->fat,
            'price' => (float) $meal->price,
        ])->values()->all();

        $lang = $locale === 'ar' ? 'Arabic' : 'English';

        $system = <<<PROMPT
You are a nutrition assistant and meal sales advisor for Diet Watchers in Saudi Arabia.
Return ONLY valid JSON: an array of objects with keys meal_id (int), reason_ar (string), reason_en (string), fit_score (int 0-100), upsell_tip (string in {$lang} — one sentence on why to buy today).
Pick the best 8 meals from the catalog for the user's full profile. Reasons must be specific to their goal, restrictions, and macros.
PROMPT;

        $user = json_encode([
            'profile' => $this->userProfilePayload(),
            'catalog' => $catalog,
        ], JSON_UNESCAPED_UNICODE);

        $raw = $gemini->generate($system, [
            ['role' => 'user', 'content' => $user],
        ], true, 0.45);

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

        $mealMap = $meals->keyBy('id');

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) use ($mealMap, $locale): ?array {
                $mealId = (int) ($row['meal_id'] ?? 0);
                $meal = $mealMap->get($mealId);
                if ($meal === null) {
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
     * @param  Collection<int, Meal>  $meals
     * @return array<int, array<string, mixed>>
     */
    private function localRecommendations(Collection $meals): array
    {
        $targetCal = (float) ($this->targetCalories ?? 0);
        $targetProtein = (float) ($this->macroTargets['protein_g'] ?? 0);
        $targetCarbs = (float) ($this->macroTargets['carbs_g'] ?? 0);
        $targetFat = (float) ($this->macroTargets['fat_g'] ?? 0);
        $locale = app()->getLocale();

        return $meals
            ->map(function (Meal $meal) use ($targetCal, $targetProtein, $targetCarbs, $targetFat): array {
                $cal = max(1, (int) $meal->calories);
                $score = (
                    abs($cal - $targetCal) / max($targetCal, 1) * 0.45
                    + abs((float) $meal->protein - $targetProtein) / max($targetProtein, 1) * 0.25
                    + abs((float) $meal->carbs - $targetCarbs) / max($targetCarbs, 1) * 0.15
                    + abs((float) $meal->fat - $targetFat) / max($targetFat, 1) * 0.15
                );

                return ['meal' => $meal, 'distance' => $score];
            })
            ->sortBy('distance')
            ->take(8)
            ->map(function (array $row) use ($locale, $targetCal): array {
                /** @var Meal $meal */
                $meal = $row['meal'];
                $fit = (int) max(55, min(98, round(100 - ($row['distance'] * 100))));

                $reason = $locale === 'ar'
                    ? sprintf('سعرات %d قريبة من هدفك (%d سعرة) مع توازن مناسب للبروتين والكارب.', (int) $meal->calories, (int) $targetCal)
                    : sprintf('%d kcal aligns with your %d kcal target with balanced macros.', (int) $meal->calories, (int) $targetCal);

                return $this->formatRecommendationCard($meal, $reason, $fit);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecommendationCard(Meal $meal, string $reason, int $fitScore): array
    {
        $locale = app()->getLocale();

        return [
            'meal_id' => $meal->id,
            'name' => (string) ($meal->translate($locale)?->name ?? $meal->name),
            'calories' => (int) $meal->calories,
            'protein' => (float) $meal->protein,
            'carbs' => (float) $meal->carbs,
            'fat' => (float) $meal->fat,
            'price' => (float) $meal->price,
            'image' => (string) ($meal->cover_image ?? ''),
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

        $langInstruction = $locale === 'ar'
            ? 'رد بالعربية ما لم يكتب العميل بالإنجليزية. كن ودوداً ومحترفاً مثل أفضل مستشار مبيعات.'
            : 'Reply in English unless the user writes in Arabic. Be warm and professional like a top sales advisor.';

        return <<<PROMPT
You are the AI nutrition consultant and customer success advisor for {$siteName}, a premium healthy meal subscription and store in Saudi Arabia.
Your job: answer questions, recommend meal plans or store products, handle objections, and guide the customer to the best next step (subscribe, browse store, or contact support).
Use the customer profile when relevant. Ask one follow-up question when information is missing.
Keep answers concise (3-6 sentences) unless the user asks for a detailed report.
{$langInstruction}

Customer profile (if available):
{$profileJson}

Company context: {$footer}
Delivery fee (SAR): {$deliveryFee}. VAT: {$vat}%.

FAQ knowledge base:
{$faqBlock}
PROMPT;
    }

    private function fallbackSupportAnswer(string $question): string
    {
        $locale = app()->getLocale();
        $needle = mb_strtolower($question);

        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('order_column')
            ->limit(40)
            ->get();

        foreach ($faqs as $faq) {
            $q = mb_strtolower((string) ($faq->translate($locale)?->question ?? ''));
            if ($q !== '' && (str_contains($needle, $q) || str_contains($q, $needle))) {
                return strip_tags((string) ($faq->translate($locale)?->answer ?? ''));
            }
        }

        return $locale === 'ar'
            ? 'شكراً لسؤالك. للمساعدة التفصيلية يُرجى زيارة صفحة الأسئلة الشائعة أو التواصل مع فريق الدعم — أو أكمل تحليلك في تبويب «تحليل الوجبة» لأعطيك توصية أدق.'
            : 'Thanks for your question. For detailed help, visit our FAQ or contact support — or complete your analysis tab for a more tailored recommendation.';
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
