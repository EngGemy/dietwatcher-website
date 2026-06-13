@php
    $isRtl = app()->getLocale() === 'ar';
    $sideClass = $isRtl ? 'ai-drawer--rtl' : 'ai-drawer--ltr';
@endphp

<div class="ai-assistant-root" wire:ignore.self>
    {{-- FAB --}}
    <button
        type="button"
        class="ai-fab"
        wire:click="togglePanel"
        aria-label="{{ __('ai.open_assistant') }}"
        title="{{ __('ai.open_assistant') }}"
        x-data="{
            showPulse: !localStorage.getItem('dw_ai_pulse_seen'),
            init() {
                if (this.showPulse) {
                    setTimeout(() => {
                        this.showPulse = false;
                        localStorage.setItem('dw_ai_pulse_seen', '1');
                    }, 8000);
                }
            }
        }"
        :class="{ 'ai-fab--pulse': showPulse && !@js($isOpen) }"
    >
        <svg class="ai-fab__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
        </svg>
    </button>

    {{-- Backdrop --}}
    @if($isOpen)
        <div class="ai-backdrop" wire:click="closePanel" aria-hidden="true"></div>
    @endif

    {{-- Drawer --}}
    <aside
        class="ai-drawer {{ $sideClass }} @if($isOpen) ai-drawer--open @endif"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('ai.panel_title') }}"
        @if(!$isOpen) aria-hidden="true" @endif
    >
        <header class="ai-drawer__head">
            <div>
                <p class="ai-drawer__eyebrow">{{ __('ai.panel_eyebrow') }}</p>
                <h2 class="ai-drawer__title">{{ __('ai.panel_title') }}</h2>
                @if($geminiConfigured)
                    <span class="ai-badge">{{ __('ai.powered_by_gemini') }}</span>
                @endif
            </div>
            <button type="button" class="ai-drawer__close" wire:click="closePanel" aria-label="{{ __('ai.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </header>

        <nav class="ai-tabs" role="tablist">
            @foreach([
                'analysis' => __('ai.tab_analysis'),
                'recommend' => __('ai.tab_recommend'),
                'support' => __('ai.tab_support'),
                'about' => __('ai.tab_about'),
            ] as $tabKey => $tabLabel)
                <button
                    type="button"
                    role="tab"
                    class="ai-tabs__btn @if($activeTab === $tabKey) ai-tabs__btn--active @endif"
                    wire:click="setTab('{{ $tabKey }}')"
                    aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                >{{ $tabLabel }}</button>
            @endforeach
        </nav>

        <div class="ai-drawer__body">
            {{-- Tab A: Analysis wizard --}}
            @if($activeTab === 'analysis')
                <section class="ai-section">
                    <div class="ai-steps">
                        @foreach([1 => __('ai.step_body'), 2 => __('ai.step_lifestyle'), 3 => __('ai.step_report')] as $num => $label)
                            <button
                                type="button"
                                class="ai-steps__item @if($analysisStep === $num) ai-steps__item--active @elseif($analysisStep > $num) ai-steps__item--done @endif"
                                wire:click="goToAnalysisStep({{ $num }})"
                            >
                                <span class="ai-steps__num">{{ $num }}</span>
                                <span class="ai-steps__label">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if($analysisStep === 1)
                        <p class="ai-section__hint">{{ __('ai.analysis_hint') }}</p>

                        <div class="ai-form-grid">
                            <label class="ai-field">
                                <span>{{ __('ai.height_cm') }}</span>
                                <input type="number" min="100" max="250" wire:model.live.debounce.300ms="heightCm" class="ai-input" />
                            </label>
                            <label class="ai-field">
                                <span>{{ __('ai.weight_kg') }}</span>
                                <input type="number" min="30" max="300" step="0.1" wire:model.live.debounce.300ms="weightKg" class="ai-input" />
                            </label>
                            <label class="ai-field">
                                <span>{{ __('ai.age') }}</span>
                                <input type="number" min="14" max="90" wire:model.live.debounce.300ms="age" class="ai-input" />
                            </label>
                            <label class="ai-field">
                                <span>{{ __('ai.gender') }}</span>
                                <select wire:model.live="gender" class="ai-input">
                                    <option value="male">{{ __('ai.gender_male') }}</option>
                                    <option value="female">{{ __('ai.gender_female') }}</option>
                                </select>
                            </label>
                            <label class="ai-field ai-field--full">
                                <span>{{ __('ai.activity') }}</span>
                                <select wire:model.live="activityLevel" class="ai-input">
                                    <option value="sedentary">{{ __('ai.activity_sedentary') }}</option>
                                    <option value="light">{{ __('ai.activity_light') }}</option>
                                    <option value="moderate">{{ __('ai.activity_moderate') }}</option>
                                    <option value="active">{{ __('ai.activity_active') }}</option>
                                    <option value="very_active">{{ __('ai.activity_very_active') }}</option>
                                </select>
                            </label>
                            <label class="ai-field ai-field--full">
                                <span>{{ __('ai.goal') }}</span>
                                <select wire:model.live="goal" class="ai-input">
                                    <option value="lose">{{ __('ai.goal_lose') }}</option>
                                    <option value="maintain">{{ __('ai.goal_maintain') }}</option>
                                    <option value="gain">{{ __('ai.goal_gain') }}</option>
                                </select>
                            </label>
                        </div>

                        @if($metricsReady && $bmi)
                            <div class="ai-bmi-pill">
                                BMI {{ $bmi }} — {{ __('ai.bmi_'.$bmiCategory) }}
                            </div>
                        @endif

                        <button type="button" class="ai-btn ai-btn--primary ai-btn--full" wire:click="nextAnalysisStep">
                            {{ __('ai.next_step') }}
                        </button>
                    @endif

                    @if($analysisStep === 2)
                        <p class="ai-section__hint">{{ __('ai.lifestyle_hint') }}</p>

                        <div class="ai-field ai-field--full">
                            <span>{{ __('ai.diet_style') }}</span>
                            <div class="ai-chips">
                                @foreach(['balanced', 'low_carb', 'keto', 'high_protein', 'calorie_deficit'] as $style)
                                    <button
                                        type="button"
                                        class="ai-chip @if($dietStyle === $style) ai-chip--active @endif"
                                        wire:click="$set('dietStyle', '{{ $style }}')"
                                    >{{ __('ai.diet_'.$style) }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="ai-field ai-field--full">
                            <span>{{ __('ai.restrictions') }}</span>
                            <div class="ai-chips">
                                @foreach(['gluten_free', 'dairy_free', 'nut_free', 'sugar_free'] as $key)
                                    <button
                                        type="button"
                                        class="ai-chip @if(in_array($key, $restrictions, true)) ai-chip--active @endif"
                                        wire:click="toggleRestriction('{{ $key }}')"
                                    >{{ __('ai.restriction_'.$key) }}</button>
                                @endforeach
                            </div>
                        </div>

                        <label class="ai-field ai-field--full">
                            <span>{{ __('ai.meals_per_day') }}</span>
                            <select wire:model.live="mealsPerDay" class="ai-input">
                                @foreach([2, 3, 4, 5] as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="ai-field ai-field--full">
                            <span>{{ __('ai.health_notes') }}</span>
                            <textarea wire:model.live.debounce.500ms="healthNotes" class="ai-input ai-textarea" rows="3" placeholder="{{ __('ai.health_notes_placeholder') }}"></textarea>
                        </label>

                        <div class="ai-btn-row">
                            <button type="button" class="ai-btn ai-btn--ghost" wire:click="prevAnalysisStep">{{ __('ai.back') }}</button>
                            <button type="button" class="ai-btn ai-btn--primary ai-btn--full" wire:click="nextAnalysisStep" wire:loading.attr="disabled" wire:target="nextAnalysisStep,generateAiReport">
                                <span wire:loading.remove wire:target="nextAnalysisStep,generateAiReport">{{ __('ai.generate_report') }}</span>
                                <span wire:loading wire:target="nextAnalysisStep,generateAiReport">{{ __('ai.analyzing') }}</span>
                            </button>
                        </div>
                    @endif

                    @if($analysisStep === 3)
                        @if($loadingReport)
                            <p class="ai-loading">{{ __('ai.analyzing') }}</p>
                        @elseif($aiReport !== [])
                            @if($reportSource === 'local')
                                <p class="ai-note">{{ __('ai.fallback_report') }}</p>
                            @endif

                            <article class="ai-report">
                                <h3 class="ai-report__title">{{ $aiReport['headline'] ?? '' }}</h3>
                                <p class="ai-report__summary">{{ $aiReport['summary'] ?? '' }}</p>

                                <div class="ai-metrics ai-metrics--compact">
                                    <div class="ai-metric-card">
                                        <span class="ai-metric-card__label">BMR</span>
                                        <span class="ai-metric-card__value">{{ number_format((float) $bmr) }}</span>
                                    </div>
                                    <div class="ai-metric-card">
                                        <span class="ai-metric-card__label">TDEE</span>
                                        <span class="ai-metric-card__value">{{ number_format((float) $tdee) }}</span>
                                    </div>
                                    <div class="ai-metric-card ai-metric-card--accent">
                                        <span class="ai-metric-card__label">{{ __('ai.target_calories') }}</span>
                                        <span class="ai-metric-card__value">{{ number_format((int) $targetCalories) }}</span>
                                    </div>
                                </div>

                                @foreach([
                                    'bmi_comment' => __('ai.report_bmi'),
                                    'calorie_strategy' => __('ai.report_calories'),
                                    'macro_advice' => __('ai.report_macros'),
                                    'meal_timing' => __('ai.report_timing'),
                                    'weekly_focus' => __('ai.report_weekly'),
                                ] as $key => $sectionTitle)
                                    @if(!empty($aiReport[$key]))
                                        <div class="ai-report__block">
                                            <h4>{{ $sectionTitle }}</h4>
                                            <p>{{ $aiReport[$key] }}</p>
                                        </div>
                                    @endif
                                @endforeach

                                @if(!empty($aiReport['lifestyle_tips']) && is_array($aiReport['lifestyle_tips']))
                                    <div class="ai-report__block">
                                        <h4>{{ __('ai.report_tips') }}</h4>
                                        <ul class="ai-report__list">
                                            @foreach($aiReport['lifestyle_tips'] as $tip)
                                                <li>{{ $tip }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($aiReport['plan_pitch']))
                                    <div class="ai-report__pitch">
                                        <span class="ai-report__pitch-label">{{ __('ai.report_recommendation') }}</span>
                                        <p>{{ $aiReport['plan_pitch'] }}</p>
                                    </div>
                                @endif

                                @if(!empty($aiReport['caution']))
                                    <p class="ai-report__caution">{{ $aiReport['caution'] }}</p>
                                @endif
                            </article>

                            <div class="ai-btn-row">
                                <button type="button" class="ai-btn ai-btn--ghost" wire:click="prevAnalysisStep">{{ __('ai.edit_answers') }}</button>
                                <button type="button" class="ai-btn ai-btn--primary ai-btn--full" wire:click="setTab('recommend')">
                                    {{ __('ai.get_recommendations') }}
                                </button>
                            </div>
                        @endif
                    @endif
                </section>
            @endif

            {{-- Tab B: Recommendations --}}
            @if($activeTab === 'recommend')
                <section class="ai-section">
                    @if(!$metricsReady)
                        <p class="ai-empty">{{ __('ai.complete_analysis_first') }}</p>
                        <button type="button" class="ai-btn ai-btn--ghost ai-btn--full" wire:click="setTab('analysis')">{{ __('ai.tab_analysis') }}</button>
                    @else
                        <div class="ai-recap">
                            <span>{{ __('ai.target_calories') }}: <strong>{{ number_format((int) $targetCalories) }}</strong></span>
                            <span>P {{ $macroTargets['protein_g'] }}g · C {{ $macroTargets['carbs_g'] }}g · F {{ $macroTargets['fat_g'] }}g</span>
                        </div>

                        <button type="button" class="ai-btn ai-btn--primary ai-btn--full" wire:click="loadRecommendations" wire:loading.attr="disabled" wire:target="loadRecommendations">
                            <span wire:loading.remove wire:target="loadRecommendations">{{ __('ai.load_recommendations') }}</span>
                            <span wire:loading wire:target="loadRecommendations">{{ __('ai.loading') }}</span>
                        </button>

                        @if($recommendationSource === 'local')
                            <p class="ai-note">{{ __('ai.fallback_recommendations') }}</p>
                        @endif

                        @if($loadingRecommendations)
                            <p class="ai-loading">{{ __('ai.loading') }}</p>
                        @elseif($catalogUnavailable)
                            <p class="ai-empty">{{ __('ai.catalog_unavailable') }}</p>
                        @elseif($recommendations === [] && $recommendationPlans === [])
                            <p class="ai-empty">{{ __('ai.no_recommendations') }}</p>
                        @else
                            @if(!empty($recommendationPath))
                                <article class="ai-path">
                                    <span class="ai-path__badge">{{ __('ai.nutritionist_label') }}</span>
                                    <h3 class="ai-path__title">{{ $recommendationPath['headline'] ?? '' }}</h3>
                                    @if(!empty($recommendationPath['summary']))
                                        <p class="ai-path__summary">{{ $recommendationPath['summary'] }}</p>
                                    @endif
                                    @if(!empty($recommendationPath['steps']) && is_array($recommendationPath['steps']))
                                        <ol class="ai-path__steps">
                                            @foreach($recommendationPath['steps'] as $step)
                                                <li>{{ $step }}</li>
                                            @endforeach
                                        </ol>
                                    @endif
                                    @if(!empty($recommendationPath['store_role']))
                                        <p class="ai-path__note">{{ $recommendationPath['store_role'] }}</p>
                                    @endif
                                    @if($recommendationTips !== [])
                                        <ul class="ai-path__tips">
                                            @foreach($recommendationTips as $tip)
                                                <li>{{ $tip }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <div class="ai-path__actions">
                                        <a href="{{ $recommendationPath['plans_url'] ?? route('meal-plans.index') }}" class="ai-btn ai-btn--primary ai-btn--sm">
                                            {{ __('ai.view_plans') }}
                                        </a>
                                        <a href="{{ $recommendationPath['store_url'] ?? route('store.index') }}" class="ai-btn ai-btn--ghost ai-btn--sm">
                                            {{ __('ai.browse_store') }}
                                        </a>
                                    </div>
                                </article>
                            @endif

                            @if($recommendationPlans !== [])
                                <div class="ai-rec-section">
                                    <h3 class="ai-rec-section__title">{{ __('ai.recommend_plans_title') }}</h3>
                                    <p class="ai-rec-section__hint">{{ __('ai.recommend_plans_hint') }}</p>
                                    <ul class="ai-plan-list">
                                        @foreach($recommendationPlans as $plan)
                                            <li class="ai-plan-card" wire:key="ai-plan-rec-{{ (int) $plan['id'] }}">
                                                <div class="ai-plan-card__top">
                                                    <div class="ai-plan-card__main">
                                                        @if(!empty($plan['image']))
                                                            <img src="{{ $plan['image'] }}" alt="" class="ai-plan-card__img" loading="lazy">
                                                        @endif
                                                        <div>
                                                            <h4 class="ai-plan-card__name">
                                                                @if(!empty($plan['url']))
                                                                    <a href="{{ $plan['url'] }}" target="_blank" rel="noopener">{{ $plan['name'] }}</a>
                                                                @else
                                                                    {{ $plan['name'] }}
                                                                @endif
                                                            </h4>
                                                            @if((int) ($plan['calories_per_day'] ?? 0) > 0)
                                                                <p class="ai-plan-card__meta">{{ (int) $plan['calories_per_day'] }} {{ __('ai.kcal_day') }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="ai-fit-badge">{{ (int) ($plan['fit_score'] ?? 0) }}%</span>
                                                </div>
                                                @if(!empty($plan['reason']))
                                                    <p class="ai-plan-card__reason">{{ $plan['reason'] }}</p>
                                                @endif
                                                <div class="ai-plan-card__foot">
                                                    @if((int) ($plan['min_price'] ?? 0) > 0)
                                                        <span class="ai-meal-card__price">{{ __('From') }} {{ number_format((int) $plan['min_price']) }} {{ __('SAR') }}</span>
                                                    @endif
                                                    @if(!empty($plan['url']))
                                                        <a href="{{ $plan['url'] }}" class="ai-btn ai-btn--primary ai-btn--sm" target="_blank" rel="noopener">
                                                            {{ __('ai.view_plan') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($recommendations !== [])
                                <div class="ai-rec-section">
                                    <h3 class="ai-rec-section__title">{{ __('ai.recommend_meals_title') }}</h3>
                                    <p class="ai-rec-section__hint">{{ __('ai.recommend_meals_hint') }}</p>
                            <ul class="ai-meal-list">
                                @foreach($recommendations as $rec)
                                    <li class="ai-meal-card" wire:key="ai-rec-{{ (int) $rec['meal_id'] }}">
                                        <div class="ai-meal-card__layout">
                                            @if(!empty($rec['image']))
                                                <a href="{{ $rec['url'] ?? '#' }}" class="ai-meal-card__thumb" target="_blank" rel="noopener">
                                                    <img src="{{ $rec['image'] }}" alt="{{ $rec['name'] }}" loading="lazy">
                                                </a>
                                            @endif
                                            <div class="ai-meal-card__body">
                                                <div class="ai-meal-card__top">
                                                    <div>
                                                        <h4 class="ai-meal-card__name">
                                                            @if(!empty($rec['url']))
                                                                <a href="{{ $rec['url'] }}" target="_blank" rel="noopener">{{ $rec['name'] }}</a>
                                                            @else
                                                                {{ $rec['name'] }}
                                                            @endif
                                                        </h4>
                                                        <p class="ai-meal-card__macros">
                                                            @if((int) $rec['calories'] > 0)
                                                                {{ (int) $rec['calories'] }} {{ __('ai.kcal') }}
                                                                · P {{ $rec['protein'] }}g
                                                                · C {{ $rec['carbs'] }}g
                                                                · F {{ $rec['fat'] }}g
                                                            @else
                                                                {{ __('ai.macros_unavailable') }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                    <span class="ai-fit-badge">{{ (int) $rec['fit_score'] }}%</span>
                                                </div>
                                                @if(!empty($rec['reason']))
                                                    <p class="ai-meal-card__reason">{{ $rec['reason'] }}</p>
                                                @endif
                                                <div class="ai-meal-card__foot">
                                                    <span class="ai-meal-card__price">{{ number_format((float) $rec['price'], 2) }} {{ __('SAR') }}</span>
                                                    <div class="ai-meal-card__actions">
                                                        @if(!empty($rec['url']))
                                                            <a href="{{ $rec['url'] }}" class="ai-btn ai-btn--ghost ai-btn--sm" target="_blank" rel="noopener">
                                                                {{ __('ai.view_meal_details') }}
                                                            </a>
                                                        @endif
                                                        <button
                                                            type="button"
                                                            class="ai-btn ai-btn--dark ai-btn--sm"
                                                            wire:click="addMealToCart({{ (int) $rec['meal_id'] }}, @js($rec['name']), {{ (float) $rec['price'] }}, @js($rec['image'] ?? ''))"
                                                        >
                                                            {{ __('ai.add_to_cart') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                                </div>
                            @endif
                        @endif
                    @endif
                </section>
            @endif

            {{-- Tab C: Smart advisor --}}
            @if($activeTab === 'support')
                <section class="ai-section ai-section--chat">
                    <p class="ai-section__hint">{{ __('ai.support_hint') }}</p>

                    <div class="ai-quick-questions">
                        @foreach($quickQuestions as $q)
                            <button
                                type="button"
                                class="ai-chip ai-chip--sm"
                                wire:click="askQuickQuestion(@js($q))"
                                wire:loading.attr="disabled"
                            >{{ $q }}</button>
                        @endforeach
                    </div>

                    <div
                        class="ai-chat"
                        wire:key="ai-chat-{{ count($supportMessages) }}-{{ $loadingSupport ? 1 : 0 }}"
                        x-data
                        x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
                    >
                        @forelse($supportMessages as $msg)
                            <div class="ai-chat__bubble ai-chat__bubble--{{ $msg['role'] === 'user' ? 'user' : 'bot' }}">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        @empty
                            <p class="ai-empty">{{ __('ai.support_empty') }}</p>
                        @endforelse
                        @if($loadingSupport)
                            <p class="ai-loading">{{ __('ai.typing') }}</p>
                        @endif
                    </div>

                    @if($supportPlanPicks !== [])
                        <div class="ai-support-picks">
                            <p class="ai-support-picks__title">{{ __('ai.support_plans_label') }}</p>
                            <ul class="ai-support-picks__list">
                                @foreach($supportPlanPicks as $plan)
                                    <li class="ai-support-pick" wire:key="ai-plan-pick-{{ (int) $plan['id'] }}">
                                        @if(!empty($plan['url']))
                                            <a href="{{ $plan['url'] }}" class="ai-support-pick__link" target="_blank" rel="noopener">
                                                <strong>{{ $plan['name'] }}</strong>
                                                @if((int) ($plan['calories_per_day'] ?? 0) > 0)
                                                    <span>· {{ (int) $plan['calories_per_day'] }} {{ __('ai.kcal') }}</span>
                                                @endif
                                            </a>
                                        @else
                                            <strong>{{ $plan['name'] }}</strong>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($supportMealPicks !== [])
                        <div class="ai-support-picks">
                            <p class="ai-support-picks__title">{{ __('ai.support_meals_label') }}</p>
                            <ul class="ai-meal-list">
                                @foreach($supportMealPicks as $rec)
                                    <li class="ai-meal-card ai-meal-card--compact" wire:key="ai-support-meal-{{ (int) $rec['meal_id'] }}">
                                        <div class="ai-meal-card__layout">
                                            @if(!empty($rec['image']))
                                                <a href="{{ $rec['url'] ?? '#' }}" class="ai-meal-card__thumb" target="_blank" rel="noopener">
                                                    <img src="{{ $rec['image'] }}" alt="{{ $rec['name'] }}" loading="lazy">
                                                </a>
                                            @endif
                                            <div class="ai-meal-card__body">
                                                <h4 class="ai-meal-card__name">
                                                    @if(!empty($rec['url']))
                                                        <a href="{{ $rec['url'] }}" target="_blank" rel="noopener">{{ $rec['name'] }}</a>
                                                    @else
                                                        {{ $rec['name'] }}
                                                    @endif
                                                </h4>
                                                <div class="ai-meal-card__foot">
                                                    <span class="ai-meal-card__price">{{ number_format((float) $rec['price'], 2) }} {{ __('SAR') }}</span>
                                                    <a href="{{ $rec['url'] ?? '#' }}" class="ai-btn ai-btn--ghost ai-btn--sm" target="_blank" rel="noopener">{{ __('ai.view_meal_details') }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form class="ai-chat-form" wire:submit.prevent="sendSupportMessage">
                        <input type="text" wire:model="supportInput" class="ai-input" placeholder="{{ __('ai.support_placeholder') }}" maxlength="500" />
                        <button type="submit" class="ai-btn ai-btn--dark" wire:loading.attr="disabled">{{ __('ai.send') }}</button>
                    </form>
                    @if($supportMessages !== [])
                        <button type="button" class="ai-link-btn" wire:click="clearSupportChat">{{ __('ai.clear_chat') }}</button>
                    @endif
                </section>
            @endif

            {{-- Tab D: About --}}
            @if($activeTab === 'about')
                <section class="ai-section">
                    <h3 class="ai-about__title">{{ $siteName }}</h3>
                    @if($aboutDescription !== '')
                        <p class="ai-about__text">{{ $aboutDescription }}</p>
                    @endif
                    @if($aboutContact !== '')
                        <p class="ai-about__contact">{{ $aboutContact }}</p>
                    @endif
                    <a href="{{ route('faqs.index') }}" class="ai-btn ai-btn--ghost ai-btn--full">{{ __('ai.view_faqs') }}</a>

                    @if($aboutFaqs !== [])
                        <div class="ai-faq-list">
                            @foreach($aboutFaqs as $faq)
                                <details class="ai-faq-item">
                                    <summary>{{ $faq['question'] }}</summary>
                                    <p>{{ $faq['answer'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </aside>

    @once
    <style>
.ai-assistant-root {
    --ai-blue: #2563EB;
    --ai-blue-dark: #1D4ED8;
    --ai-blue-light: #3B82F6;
    --ai-blue-soft: #EAF4FF;
    --ai-blue-border: #DBEAFE;
    --ai-green: #10B981;
    --ai-green-soft: #ECFDF5;
    --ai-text: #1F2937;
    --ai-muted: #6B7280;
    font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
}
[dir="rtl"] .ai-assistant-root { font-family: 'Almarai', ui-sans-serif, system-ui, sans-serif; }

.ai-fab {
    position: fixed;
    bottom: 1.5rem;
    z-index: 9990;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 999px;
    border: 0;
    background: linear-gradient(135deg, var(--ai-blue-light) 0%, var(--ai-blue) 55%, var(--ai-blue-dark) 100%);
    color: #fff;
    box-shadow: 0 10px 28px -8px rgba(37, 99, 235, .55);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
}
[dir="ltr"] .ai-fab { right: 1.25rem; }
[dir="rtl"] .ai-fab { left: 1.25rem; }
.ai-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 32px -6px rgba(37, 99, 235, .65);
    filter: brightness(1.05);
}
.ai-fab__icon { width: 1.5rem; height: 1.5rem; }
.ai-fab--pulse { animation: aiFabPulse 2.2s ease-in-out infinite; }
@keyframes aiFabPulse {
    0%, 100% { box-shadow: 0 10px 28px -8px rgba(37, 99, 235, .55); }
    50% { box-shadow: 0 10px 28px -8px rgba(37, 99, 235, .55), 0 0 0 10px rgba(37, 99, 235, .12); }
}

.ai-backdrop {
    position: fixed; inset: 0; z-index: 9991;
    background: rgba(15, 23, 42, .4);
    backdrop-filter: blur(2px);
}

.ai-drawer {
    position: fixed; top: 0; bottom: 0; z-index: 9992;
    width: min(420px, 100vw);
    background: #fff;
    border-inline-start: 1px solid var(--ai-blue-border);
    display: flex; flex-direction: column;
    transform: translateX(100%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    box-shadow: -8px 0 32px rgba(37, 99, 235, .08);
}
.ai-drawer--ltr { right: 0; transform: translateX(100%); }
.ai-drawer--rtl { left: 0; transform: translateX(-100%); border-inline-start: 0; border-inline-end: 1px solid var(--ai-blue-border); box-shadow: 8px 0 32px rgba(37, 99, 235, .08); }
.ai-drawer--open.ai-drawer--ltr { transform: translateX(0); }
.ai-drawer--open.ai-drawer--rtl { transform: translateX(0); }

@media (max-width: 640px) {
    .ai-drawer { width: 100vw; }
}

.ai-drawer__head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
    padding: 1.1rem 1.25rem .95rem;
    border-bottom: 1px solid var(--ai-blue-border);
    background: linear-gradient(180deg, var(--ai-blue-soft) 0%, #fff 100%);
}
.ai-drawer__eyebrow {
    font-size: .68rem; letter-spacing: .08em; text-transform: uppercase;
    color: var(--ai-green); font-weight: 800; margin: 0;
}
.ai-drawer__title {
    font-size: 1.15rem; font-weight: 800; color: var(--ai-text); margin: .15rem 0 0;
    background: linear-gradient(120deg, var(--ai-blue) 0%, var(--ai-blue-light) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
.ai-drawer__close {
    width: 2rem; height: 2rem; border: 1px solid var(--ai-blue-border); border-radius: 8px;
    background: #fff; color: var(--ai-blue); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, border-color .15s;
}
.ai-drawer__close:hover { background: var(--ai-blue-soft); border-color: #BFDBFE; }
.ai-drawer__close svg { width: 1rem; height: 1rem; }

.ai-tabs {
    display: flex; gap: .25rem; padding: .5rem .75rem;
    border-bottom: 1px solid var(--ai-blue-border);
    background: #FAFCFF; overflow-x: auto;
}
.ai-tabs__btn {
    flex: 1; min-width: max-content; border: 0; background: transparent;
    color: var(--ai-muted); font-size: .72rem; font-weight: 700;
    padding: .45rem .5rem; border-radius: 8px; cursor: pointer; white-space: nowrap;
    transition: background .15s, color .15s;
}
.ai-tabs__btn:hover { color: var(--ai-blue); background: var(--ai-blue-soft); }
.ai-tabs__btn--active {
    background: linear-gradient(120deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%);
    color: #fff;
    box-shadow: 0 4px 12px -4px rgba(37, 99, 235, .45);
}

.ai-drawer__body { flex: 1; overflow-y: auto; padding: 1rem 1.1rem 1.25rem; }
.ai-section__hint { font-size: .82rem; color: var(--ai-muted); margin: 0 0 .9rem; line-height: 1.5; }

.ai-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin-bottom: 1rem; }
.ai-field { display: flex; flex-direction: column; gap: .25rem; }
.ai-field span { font-size: .72rem; font-weight: 700; color: #374151; }
.ai-field--full { grid-column: 1 / -1; }
.ai-input {
    border: 1px solid #D1D5DB; border-radius: 8px; padding: .5rem .65rem;
    font-size: .86rem; color: var(--ai-text); background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.ai-input:focus {
    outline: none; border-color: var(--ai-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}

.ai-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: 1rem; }
.ai-metric-card {
    border: 1px solid var(--ai-blue-border); border-radius: 10px;
    padding: .65rem .5rem; text-align: center; background: #FAFCFF;
}
.ai-metric-card--accent {
    background: linear-gradient(135deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%);
    color: #fff; border-color: var(--ai-blue);
    box-shadow: 0 6px 16px -6px rgba(37, 99, 235, .45);
}
.ai-metric-card__label { display: block; font-size: .62rem; font-weight: 800; letter-spacing: .06em; opacity: .85; }
.ai-metric-card__value { display: block; font-size: 1.15rem; font-weight: 800; line-height: 1.1; margin-top: .15rem; }
.ai-metric-card__unit { display: block; font-size: .62rem; opacity: .8; margin-top: .1rem; }

.ai-macros { border: 1px solid var(--ai-blue-border); border-radius: 12px; padding: .85rem; margin-bottom: 1rem; background: #FAFCFF; }
.ai-macros__title { font-size: .78rem; font-weight: 800; color: var(--ai-text); margin: 0 0 .6rem; text-transform: uppercase; letter-spacing: .06em; }
.ai-macros__bar { display: flex; height: 8px; border-radius: 999px; overflow: hidden; background: #E5E7EB; margin-bottom: .65rem; }
.ai-macros__seg--p { background: var(--ai-blue); }
.ai-macros__seg--c { background: var(--ai-green); }
.ai-macros__seg--f { background: #93C5FD; }
.ai-macros__grid { display: grid; gap: .35rem; }
.ai-macro-row { display: grid; grid-template-columns: 1fr auto auto; gap: .5rem; align-items: center; font-size: .8rem; color: #374151; }
.ai-macro-row strong { font-weight: 800; color: var(--ai-text); }
.ai-macro-row em { font-style: normal; font-size: .72rem; color: var(--ai-muted); min-width: 2.2rem; text-align: end; }

.ai-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
    border-radius: 8px; font-size: .82rem; font-weight: 700; padding: .55rem .9rem;
    border: 1px solid transparent; cursor: pointer;
    transition: background .15s, color .15s, filter .15s, box-shadow .15s;
}
.ai-btn--primary {
    background: linear-gradient(120deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%);
    color: #fff; border-color: var(--ai-blue);
    box-shadow: 0 6px 16px -8px rgba(37, 99, 235, .55);
}
.ai-btn--primary:hover { filter: brightness(1.05); }
.ai-btn--dark {
    background: linear-gradient(120deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%);
    color: #fff;
    box-shadow: 0 4px 12px -6px rgba(37, 99, 235, .45);
}
.ai-btn--dark:hover { filter: brightness(1.05); }
.ai-btn--ghost { background: #fff; color: var(--ai-blue); border-color: var(--ai-blue-border); }
.ai-btn--ghost:hover { background: var(--ai-blue-soft); border-color: #BFDBFE; }
.ai-btn--full { width: 100%; }
.ai-btn--sm { padding: .4rem .65rem; font-size: .75rem; }

.ai-recap {
    display: flex; flex-direction: column; gap: .2rem; font-size: .78rem; color: var(--ai-muted);
    margin-bottom: .75rem; padding: .6rem .7rem; background: var(--ai-blue-soft);
    border-radius: 8px; border: 1px solid var(--ai-blue-border);
}
.ai-note, .ai-loading, .ai-empty { font-size: .8rem; color: var(--ai-muted); margin: .75rem 0 0; }
.ai-meal-list { list-style: none; margin: .85rem 0 0; padding: 0; display: grid; gap: .65rem; }
.ai-meal-card { border: 1px solid var(--ai-blue-border); border-radius: 12px; padding: .75rem; background: #fff; }
.ai-meal-card__layout { display: flex; gap: .65rem; align-items: flex-start; }
.ai-meal-card__thumb { flex: 0 0 72px; width: 72px; height: 72px; border-radius: 10px; overflow: hidden; border: 1px solid var(--ai-blue-border); }
.ai-meal-card__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ai-meal-card__body { flex: 1; min-width: 0; }
.ai-meal-card__top { display: flex; justify-content: space-between; gap: .5rem; }
.ai-meal-card__name { margin: 0; font-size: .9rem; font-weight: 800; color: var(--ai-text); }
.ai-meal-card__name a { color: inherit; text-decoration: none; }
.ai-meal-card__name a:hover { color: var(--ai-blue); text-decoration: underline; }
.ai-meal-card__macros { margin: .2rem 0 0; font-size: .72rem; color: var(--ai-muted); }
.ai-meal-card__actions { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }
.ai-meal-card--compact { padding: .55rem; }
.ai-support-picks { margin-top: .65rem; }
.ai-support-picks__title { margin: 0 0 .4rem; font-size: .78rem; font-weight: 800; color: var(--ai-blue); }
.ai-support-picks__list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .35rem; }
.ai-support-pick { font-size: .78rem; color: #374151; }
.ai-support-pick__link { color: inherit; text-decoration: none; }
.ai-support-pick__link:hover { color: var(--ai-blue); text-decoration: underline; }
.ai-chat__bubble a { color: var(--ai-blue); word-break: break-word; }
.ai-fit-badge {
    font-size: .72rem; font-weight: 800;
    background: linear-gradient(120deg, var(--ai-green) 0%, #059669 100%);
    color: #fff; border-radius: 999px; padding: .15rem .5rem; height: fit-content;
}
.ai-meal-card__reason { font-size: .78rem; color: #374151; margin: .5rem 0 0; line-height: 1.45; }
.ai-meal-card__foot { display: flex; align-items: center; justify-content: space-between; margin-top: .6rem; }
.ai-meal-card__price { font-size: .82rem; font-weight: 800; color: var(--ai-blue); }

.ai-section--chat { display: flex; flex-direction: column; min-height: 320px; }
.ai-chat { flex: 1; min-height: 200px; max-height: 340px; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; margin-bottom: .65rem; padding: .25rem; }
.ai-chat__bubble { max-width: 92%; padding: .6rem .75rem; border-radius: 10px; font-size: .8rem; line-height: 1.45; }
.ai-chat__bubble--user {
    align-self: flex-end;
    background: linear-gradient(120deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%);
    color: #fff; border-end-end-radius: 2px;
}
.ai-chat__bubble--bot {
    align-self: flex-start; background: var(--ai-blue-soft); color: var(--ai-text);
    border: 1px solid var(--ai-blue-border); border-end-start-radius: 2px;
}
.ai-chat-form { display: flex; gap: .4rem; }
.ai-chat-form .ai-input { flex: 1; }
.ai-link-btn { margin-top: .5rem; background: none; border: 0; color: var(--ai-blue); font-size: .75rem; cursor: pointer; text-decoration: underline; }

.ai-about__title { font-size: 1rem; font-weight: 800; margin: 0 0 .5rem; color: var(--ai-text); }
.ai-about__text, .ai-about__contact { font-size: .82rem; color: #374151; line-height: 1.55; margin: 0 0 .75rem; }
.ai-faq-list { margin-top: 1rem; display: grid; gap: .45rem; }
.ai-faq-item { border: 1px solid var(--ai-blue-border); border-radius: 8px; padding: .55rem .65rem; font-size: .78rem; background: #FAFCFF; }
.ai-faq-item summary { font-weight: 700; cursor: pointer; color: var(--ai-blue); }
.ai-faq-item p { margin: .45rem 0 0; color: var(--ai-muted); line-height: 1.45; }

.ai-badge {
    display: inline-block; margin-top: .35rem; font-size: .62rem; font-weight: 800;
    letter-spacing: .04em; text-transform: uppercase; color: var(--ai-blue);
    background: var(--ai-blue-soft); border: 1px solid var(--ai-blue-border);
    border-radius: 999px; padding: .15rem .5rem;
}

.ai-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: .35rem; margin-bottom: 1rem; }
.ai-steps__item {
    display: flex; flex-direction: column; align-items: center; gap: .2rem;
    border: 1px solid var(--ai-blue-border); border-radius: 10px; background: #fff;
    padding: .45rem .25rem; cursor: pointer; transition: border-color .15s, background .15s;
}
.ai-steps__item--active { border-color: var(--ai-blue); background: var(--ai-blue-soft); }
.ai-steps__item--done .ai-steps__num { background: var(--ai-green); color: #fff; }
.ai-steps__num {
    width: 1.35rem; height: 1.35rem; border-radius: 999px; display: flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 800; background: #E5E7EB; color: #374151;
}
.ai-steps__item--active .ai-steps__num { background: var(--ai-blue); color: #fff; }
.ai-steps__label { font-size: .62rem; font-weight: 700; color: var(--ai-muted); text-align: center; line-height: 1.2; }
.ai-steps__item--active .ai-steps__label { color: var(--ai-blue); }

.ai-bmi-pill {
    font-size: .78rem; font-weight: 700; color: var(--ai-blue);
    background: var(--ai-blue-soft); border: 1px solid var(--ai-blue-border);
    border-radius: 8px; padding: .5rem .65rem; margin-bottom: .85rem; text-align: center;
}

.ai-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .35rem; }
.ai-chip {
    border: 1px solid var(--ai-blue-border); background: #fff; color: #374151;
    border-radius: 999px; padding: .35rem .65rem; font-size: .72rem; font-weight: 700;
    cursor: pointer; transition: background .15s, color .15s, border-color .15s;
}
.ai-chip:hover { border-color: #BFDBFE; background: var(--ai-blue-soft); color: var(--ai-blue); }
.ai-chip--active { background: linear-gradient(120deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%); color: #fff; border-color: var(--ai-blue); }
.ai-chip--sm { font-size: .68rem; padding: .3rem .55rem; }

.ai-textarea { resize: vertical; min-height: 4.5rem; }
.ai-btn-row { display: flex; gap: .5rem; margin-top: .85rem; align-items: stretch; }
.ai-btn-row .ai-btn--full { flex: 1; }

.ai-quick-questions { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .75rem; }

.ai-report {
    border: 1px solid var(--ai-blue-border); border-radius: 12px; padding: .9rem;
    background: linear-gradient(180deg, var(--ai-blue-soft) 0%, #fff 35%);
    margin-bottom: .85rem;
}
.ai-report__title { margin: 0 0 .5rem; font-size: 1rem; font-weight: 800; color: var(--ai-text); }
.ai-report__summary { margin: 0 0 .75rem; font-size: .82rem; color: #374151; line-height: 1.55; }
.ai-report__block { margin-bottom: .65rem; }
.ai-report__block h4 { margin: 0 0 .25rem; font-size: .72rem; font-weight: 800; color: var(--ai-blue); text-transform: uppercase; letter-spacing: .05em; }
.ai-report__block p { margin: 0; font-size: .8rem; color: #374151; line-height: 1.5; }
.ai-report__list { margin: .25rem 0 0; padding-inline-start: 1.1rem; font-size: .8rem; color: #374151; }
.ai-report__list li { margin-bottom: .25rem; }
.ai-report__pitch {
    margin-top: .75rem; padding: .65rem .75rem; border-radius: 10px;
    background: linear-gradient(120deg, var(--ai-blue-light) 0%, var(--ai-blue) 100%);
    color: #fff;
}
.ai-report__pitch-label { display: block; font-size: .65rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .9; margin-bottom: .25rem; }
.ai-report__pitch p { margin: 0; font-size: .82rem; line-height: 1.5; }
.ai-report__caution { font-size: .75rem; color: #B45309; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: .5rem .65rem; margin-top: .65rem; }

.ai-path {
    margin-top: .85rem; padding: 1rem; border-radius: 14px;
    background: linear-gradient(135deg, rgba(39,159,249,.08), rgba(63,181,54,.07));
    border: 1px solid rgba(39,159,249,.18);
}
.ai-path__badge {
    display: inline-flex; padding: .2rem .55rem; border-radius: 999px;
    font-size: .65rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
    background: #fff; color: var(--ai-blue); border: 1px solid var(--ai-blue-border);
}
.ai-path__title { margin: .55rem 0 .35rem; font-size: .98rem; font-weight: 800; color: var(--ai-text); }
.ai-path__summary { margin: 0 0 .65rem; font-size: .82rem; line-height: 1.55; color: #374151; }
.ai-path__steps { margin: 0 0 .65rem; padding-inline-start: 1.15rem; font-size: .78rem; color: #374151; line-height: 1.5; }
.ai-path__steps li { margin-bottom: .35rem; }
.ai-path__note { margin: 0 0 .55rem; font-size: .76rem; color: #64748B; font-style: italic; }
.ai-path__tips { margin: 0; padding-inline-start: 1.1rem; font-size: .76rem; color: #475569; }
.ai-path__tips li { margin-bottom: .25rem; }
.ai-path__actions { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .75rem; }

.ai-rec-section { margin-top: 1rem; }
.ai-rec-section__title { margin: 0 0 .25rem; font-size: .92rem; font-weight: 800; color: var(--ai-text); }
.ai-rec-section__hint { margin: 0 0 .55rem; font-size: .74rem; color: var(--ai-muted); line-height: 1.45; }

.ai-plan-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .6rem; }
.ai-plan-card {
    border: 1px solid #C7E8C8; border-radius: 12px; padding: .75rem;
    background: linear-gradient(180deg, #fff, #F8FFF8);
}
.ai-plan-card__top { display: flex; justify-content: space-between; gap: .5rem; align-items: flex-start; }
.ai-plan-card__main { display: flex; gap: .55rem; align-items: center; min-width: 0; }
.ai-plan-card__img { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid #E5E7EB; flex-shrink: 0; }
.ai-plan-card__name { margin: 0; font-size: .88rem; font-weight: 800; }
.ai-plan-card__name a { color: inherit; text-decoration: none; }
.ai-plan-card__name a:hover { color: var(--ai-blue); text-decoration: underline; }
.ai-plan-card__meta { margin: .15rem 0 0; font-size: .72rem; color: var(--ai-muted); }
.ai-plan-card__reason { margin: .5rem 0 0; font-size: .77rem; color: #374151; line-height: 1.45; }
.ai-plan-card__foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .55rem; flex-wrap: wrap; }
.ai-metrics--compact { margin-bottom: .75rem; }
    </style>
    @endonce
</div>
