@php
    use App\Support\SubscriptionLifecycle;

    $sub = $subscription;
    $status = (string) ($sub['status'] ?? '');
    $statusNorm = SubscriptionLifecycle::normalize($status);
    $statusKey = 'account.status_'.$statusNorm;
    $statusLabel = __($statusKey);
    if ($statusLabel === $statusKey) {
        $statusLabel = $status !== '' ? ucfirst($status) : '—';
    }
    $canPause = SubscriptionLifecycle::canPause($status);
    $canResume = SubscriptionLifecycle::canResume($status);
    $canCancel = SubscriptionLifecycle::canCancel($status);
    $canManageDays = SubscriptionLifecycle::canSkipOrRestoreDay($status);
    $canReplace = SubscriptionLifecycle::canReplaceMeal($status);
    $isTerminal = SubscriptionLifecycle::isTerminal($status);

    $plan = $sub['plan']['name'] ?? $sub['program']['name'] ?? $sub['name'] ?? '';
    if (is_array($plan)) {
        $plan = $plan[app()->getLocale()] ?? $plan['en'] ?? '';
    }
    $startAt = $sub['start_at'] ?? $sub['started_at'] ?? '';
    $endAt = $sub['end_at'] ?? $sub['ended_at'] ?? '';
    $remain = $sub['remaining_days'] ?? $sub['days_remaining'] ?? null;
    $total = $sub['total'] ?? $sub['amount'] ?? null;
    $pausedUntil = $sub['reactivate_date'] ?? $sub['paused_until'] ?? $sub['resume_at'] ?? null;

    $previewRefund = $cancelPreview['refund'] ?? $cancelPreview['refund_amount'] ?? $cancelPreview['amount'] ?? null;
    $previewMessage = $cancelPreview['message'] ?? $cancelPreview['note'] ?? $cancelPreview['description'] ?? null;
    if (is_array($previewMessage)) {
        $previewMessage = $previewMessage[app()->getLocale()] ?? $previewMessage['en'] ?? null;
    }
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <a href="{{ route('account.subscriptions.index') }}" class="text-sm text-blue-600 font-semibold inline-flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                {{ __('account.back_to_subscriptions') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $plan ?: (__('account.subscription') . ' #' . $subscriptionId) }}</h1>
            @if($isTerminal)
                <p class="text-sm text-gray-500 mt-1">{{ __('account.subscription_ended_hint') }}</p>
            @endif
        </div>
    </div>

    @if($notice)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>
    @endif
    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 flex items-center justify-between gap-3 flex-wrap">
            <span>{{ $error }}</span>
            @if($error === __('account.login_required'))
                <a href="{{ route('account.login') }}" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.go_to_login') }}</a>
            @endif
        </div>
    @endif

    {{-- Mobile-style action bar (same as app) --}}
    @if($canPause || $canResume || $canCancel)
        <section class="acc-card p-4" id="manage">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">{{ __('account.manage_subscription') }}</p>
            <div class="flex flex-wrap gap-2">
                @if($canPause)
                    <button type="button" class="acc-btn acc-btn--muted acc-btn--sm" wire:click="openPause">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
                        {{ __('account.pause') }}
                    </button>
                @endif
                @if($canResume)
                    <button type="button" class="acc-btn acc-btn--primary acc-btn--sm" wire:click="resume" wire:loading.attr="disabled">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z"/></svg>
                        {{ __('account.resume') }}
                    </button>
                @endif
                @if($canCancel)
                    <button type="button" class="acc-btn acc-btn--danger acc-btn--sm" wire:click="openCancel">
                        {{ __('account.cancel_subscription') }}
                    </button>
                @endif
            </div>
            @if($canResume && $pausedUntil)
                <p class="text-xs text-amber-700 mt-2">{{ __('account.scheduled_resume') }}: <strong>{{ $pausedUntil }}</strong></p>
            @endif
        </section>
    @endif

    {{-- Overview --}}
    <section class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.status') }}</span>
            <span class="acc-stat__value" style="font-size:1rem;">
                @php
                    $chipClass = SubscriptionLifecycle::isActive($status) ? 'acc-chip--success'
                        : (SubscriptionLifecycle::isPaused($status) ? 'acc-chip--warn'
                        : ($isTerminal ? 'acc-chip--danger' : 'acc-chip--muted'));
                @endphp
                <span class="acc-chip {{ $chipClass }}">{{ $status ? $statusLabel : '—' }}</span>
            </span>
        </div>
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.start_date') }}</span>
            <span class="acc-stat__value" style="font-size:1rem;">{{ $startAt ?: '—' }}</span>
        </div>
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.end_date') }}</span>
            <span class="acc-stat__value" style="font-size:1rem;">{{ $endAt ?: '—' }}</span>
        </div>
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.days_remaining') }}</span>
            <span class="acc-stat__value">{{ $remain ?? '—' }}</span>
        </div>
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.total') }}</span>
            <span class="acc-stat__value" style="font-size:1.1rem;">
                @if($total !== null) <x-sar :amount="(float) $total" class="text-xs text-gray-900" /> @else — @endif
            </span>
        </div>
    </section>

    {{-- Daily meals --}}
    <section class="acc-card">
        <div class="acc-card-head">
            <span>{{ __('account.daily_meals') }}</span>
            <div class="flex items-center gap-2">
                <input type="date"
                       class="acc-btn acc-btn--muted acc-btn--sm"
                       style="padding:.4rem .7rem;"
                       wire:model.live="focusDate" />
                <button type="button" class="acc-btn acc-btn--muted acc-btn--sm" wire:click="load" wire:loading.attr="disabled">
                    {{ __('account.refresh') }}
                </button>
            </div>
        </div>
        <div class="acc-card-body">
            @if($loading)
                <div class="acc-empty">{{ __('account.loading') }}</div>
            @elseif(empty($days))
                <div class="acc-empty">{{ __('account.no_meals_for_day') }}</div>
            @else
                <div class="space-y-4">
                    @foreach($days as $day)
                        @php
                            $dDate = $day['date'] ?? $day['delivery_date'] ?? '';
                            $dietId = (int) ($day['id'] ?? $day['diet_id'] ?? 0);
                            $dayStatus = (string) ($day['status'] ?? '');
                            $dayStatusNorm = SubscriptionLifecycle::normalize($dayStatus);
                            $dayStatusKey = 'account.status_'.$dayStatusNorm;
                            $dayStatusLabel = __($dayStatusKey);
                            if ($dayStatusLabel === $dayStatusKey) {
                                $dayStatusLabel = $dayStatus !== '' ? ucfirst($dayStatus) : '';
                            }
                            $meals = $day['meals'] ?? $day['menus'] ?? [];
                            if (! is_array($meals)) {
                                $meals = [];
                            }
                            $isSkipped = SubscriptionLifecycle::isDaySkipped($dayStatus);
                            $canActOnDay = $canManageDays && $dietId > 0 && SubscriptionLifecycle::isFutureOrToday($dDate);
                        @endphp
                        <div class="border border-gray-100 rounded-xl p-4 {{ $isSkipped ? 'bg-amber-50/40' : 'bg-white' }}">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $dDate }}</div>
                                    @if($dayStatusLabel)
                                        <span class="acc-chip {{ $isSkipped ? 'acc-chip--warn' : 'acc-chip--success' }}">{{ $dayStatusLabel }}</span>
                                    @endif
                                </div>
                                @if($canActOnDay)
                                    <div class="flex items-center gap-2">
                                        @if($isSkipped)
                                            <button type="button"
                                                    class="acc-btn acc-btn--primary acc-btn--sm"
                                                    wire:click="restoreDay({{ $dietId }}, '{{ $dDate }}')"
                                                    wire:loading.attr="disabled">
                                                {{ __('account.restore_day') }}
                                            </button>
                                        @else
                                            <button type="button"
                                                    class="acc-btn acc-btn--muted acc-btn--sm"
                                                    wire:click="skipDay({{ $dietId }}, '{{ $dDate }}')"
                                                    wire:confirm="{{ __('account.confirm_skip_day') }}"
                                                    wire:loading.attr="disabled">
                                                {{ __('account.skip_day') }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if(! empty($meals))
                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($meals as $meal)
                                        @php
                                            $mealName = $meal['name'] ?? $meal['meal']['name'] ?? '';
                                            if (is_array($mealName)) {
                                                $mealName = $mealName[app()->getLocale()] ?? $mealName['en'] ?? '';
                                            }
                                            $mealType = $meal['meal_type']['name'] ?? $meal['type'] ?? '';
                                            if (is_array($mealType)) {
                                                $mealType = $mealType[app()->getLocale()] ?? $mealType['en'] ?? '';
                                            }
                                            $img = $meal['image'] ?? $meal['image_url'] ?? $meal['meal']['image'] ?? '';
                                            $mealId = (int) ($meal['meal_id'] ?? $meal['id'] ?? $meal['meal']['id'] ?? 0);
                                            $planMenuId = (int) ($meal['plan_menu_id'] ?? $meal['menu_id'] ?? 0);
                                            $showReplace = $canReplace && ! $isSkipped && $dietId > 0 && $mealId > 0 && $planMenuId > 0 && $dDate !== '';
                                        @endphp
                                        <li class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                                            @if($img)
                                                <img src="{{ $img }}" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" onerror="this.style.display='none'">
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-semibold text-gray-900 truncate">{{ $mealName ?: '—' }}</div>
                                                @if($mealType)
                                                    <div class="text-xs text-gray-500">{{ $mealType }}</div>
                                                @endif
                                            </div>
                                            @if($showReplace)
                                                <button type="button"
                                                        class="acc-btn acc-btn--ghost acc-btn--sm shrink-0"
                                                        wire:click="openReplace({{ $planMenuId }}, {{ $dietId }}, {{ $mealId }}, '{{ $dDate }}')"
                                                        wire:loading.attr="disabled">
                                                    {{ __('account.replace_meal') }}
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Cancel modal --}}
    @if($showCancel)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-slate-900/60" wire:click.self="closeCancel">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('account.confirm_cancel_title') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('account.confirm_cancel_hint') }}</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.cancel_effective_date') }}</label>
                        <input type="date" wire:model.live="cancelDate" class="w-full rounded-lg border border-gray-200 p-2 text-sm" />
                    </div>

                    @if($cancelPreviewLoading)
                        <p class="text-sm text-gray-500">{{ __('account.loading') }}</p>
                    @elseif(! empty($cancelPreview))
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-sm text-gray-700 space-y-1">
                            @if($previewMessage)
                                <p>{{ $previewMessage }}</p>
                            @endif
                            @if(is_numeric($previewRefund))
                                <p class="font-semibold">{{ __('account.estimated_refund') }}: <x-sar :amount="(float) $previewRefund" class="text-xs" /></p>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">{{ __('account.cancel_reason') }}</label>
                        <textarea wire:model="cancelReason" rows="3" class="mt-1 w-full rounded-lg border border-gray-200 p-3 text-sm focus:outline-none focus:border-blue-400" placeholder="{{ __('account.cancel_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="p-5 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" class="acc-btn acc-btn--muted" wire:click="closeCancel">{{ __('Cancel') }}</button>
                    <button type="button" class="acc-btn acc-btn--danger" wire:click="confirmCancel" wire:loading.attr="disabled">
                        {{ __('account.cancel_subscription') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pause modal --}}
    @if($showPause)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-slate-900/60" wire:click.self="closePause">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('account.pause_title') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('account.pause_hint') }}</p>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.pause_from') }}</label>
                        <input type="date" wire:model="pausedDate" class="w-full rounded-lg border border-gray-200 p-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.resume_on') }}</label>
                        <input type="date" wire:model="reactivateDate" class="w-full rounded-lg border border-gray-200 p-2 text-sm" />
                    </div>
                </div>
                <div class="p-5 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" class="acc-btn acc-btn--muted" wire:click="closePause">{{ __('Cancel') }}</button>
                    <button type="button" class="acc-btn acc-btn--primary" wire:click="confirmPause" wire:loading.attr="disabled">
                        {{ __('account.pause') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Replace meal modal --}}
    @if($showReplace)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-slate-900/60" wire:click.self="closeReplace">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('account.replace_meal_title') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('account.replace_meal_hint') }}</p>
                </div>
                <div class="p-5">
                    @if($replaceLoading)
                        <div class="acc-empty">{{ __('account.loading') }}</div>
                    @elseif(empty($replaceOptions))
                        <div class="acc-empty">{{ __('account.no_replacement_options') }}</div>
                    @else
                        <ul class="space-y-2">
                            @foreach($replaceOptions as $opt)
                                @php
                                    $optId = (int) ($opt['diet_id'] ?? $opt['id'] ?? 0);
                                    $optName = $opt['name'] ?? $opt['meal']['name'] ?? '';
                                    if (is_array($optName)) {
                                        $optName = $optName[app()->getLocale()] ?? $optName['en'] ?? '';
                                    }
                                    $optImg = $opt['image'] ?? $opt['image_url'] ?? $opt['meal']['image'] ?? '';
                                @endphp
                                <li>
                                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition {{ $selectedReplaceDietId === $optId ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:bg-gray-50' }}">
                                        <input type="radio"
                                               class="text-blue-600"
                                               wire:model.live="selectedReplaceDietId"
                                               value="{{ $optId }}" />
                                        @if($optImg)
                                            <img src="{{ $optImg }}" alt="" class="w-10 h-10 rounded-lg object-cover" onerror="this.style.display='none'">
                                        @endif
                                        <span class="text-sm font-semibold text-gray-900">{{ $optName ?: ('#'.$optId) }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="p-5 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" class="acc-btn acc-btn--muted" wire:click="closeReplace">{{ __('Cancel') }}</button>
                    <button type="button"
                            class="acc-btn acc-btn--primary"
                            wire:click="confirmReplace"
                            wire:loading.attr="disabled"
                            @if(! $selectedReplaceDietId) disabled @endif>
                        {{ __('account.confirm_replace') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
