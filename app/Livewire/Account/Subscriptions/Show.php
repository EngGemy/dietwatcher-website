<?php

declare(strict_types=1);

namespace App\Livewire\Account\Subscriptions;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use App\Support\SubscriptionLifecycle;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('تفاصيل الاشتراك')]
class Show extends Component
{
    use NormalizesAccountPayload;

    public int $subscriptionId = 0;

    public string $focusDate = '';

    public array $subscription = [];

    public array $days = [];

    public bool $loading = true;

    public string $error = '';

    public string $notice = '';

    // Cancel modal
    public bool $showCancel = false;

    public string $cancelReason = '';

    public string $cancelDate = '';

    public array $cancelPreview = [];

    public bool $cancelPreviewLoading = false;

    // Pause modal
    public bool $showPause = false;

    public string $pausedDate = '';

    public string $reactivateDate = '';

    // Replace meal modal
    public bool $showReplace = false;

    public bool $replaceLoading = false;

    public array $replaceOptions = [];

    public ?int $replaceDietId = null;

    public ?int $replaceMealId = null;

    public ?int $replacePlanMenuId = null;

    public string $replaceDate = '';

    public ?int $selectedReplaceDietId = null;

    public function mount(AccountApiService $api, int $id): void
    {
        $this->subscriptionId = $id;
        if ($this->focusDate === '') {
            $this->focusDate = now()->format('Y-m-d');
        }
        $this->load($api);
    }

    public function updatedFocusDate(AccountApiService $api): void
    {
        $this->load($api);
    }

    public function load(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';
        $result = $api->showSubscription($this->subscriptionId, $this->focusDate);

        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->subscription = [];
            $this->days = [];
            $this->loading = false;

            return;
        }

        $data = $result['data'] ?? [];
        $sub = $this->extractOne($data, ['subscription']);
        $days = $this->extractRows($sub['days'] ?? null, ['menu_days', 'subscription_days']);
        if ($days === []) {
            $days = $this->extractRows($data, ['days', 'menu_days', 'subscription_days']);
        }

        $this->subscription = $sub;
        $this->days = $days;
        $this->loading = false;
    }

    public function skipDay(AccountApiService $api, int $dietId, string $date): void
    {
        $this->error = $this->notice = '';
        if ($dietId <= 0 || $date === '') {
            $this->error = __('account.invalid_input');

            return;
        }
        $status = (string) ($this->subscription['status'] ?? '');
        if (! SubscriptionLifecycle::canSkipOrRestoreDay($status)) {
            $this->error = __('account.action_not_available');

            return;
        }
        $result = $api->skipDay($dietId, $date, $date, $this->subscriptionId);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }
        $this->notice = __('account.day_skipped');
        $this->load($api);
    }

    public function restoreDay(AccountApiService $api, int $dietId, string $date): void
    {
        $this->error = $this->notice = '';
        if ($dietId <= 0 || $date === '') {
            $this->error = __('account.invalid_input');

            return;
        }
        $result = $api->restoreDay($dietId, $date, $this->subscriptionId);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }
        $this->notice = __('account.day_restored');
        $this->load($api);
    }

    public function openCancel(AccountApiService $api): void
    {
        $this->showCancel = true;
        $this->cancelReason = '';
        $this->cancelDate = $this->focusDate ?: now()->format('Y-m-d');
        $this->cancelPreview = [];
        $this->loadCancelPreview($api);
    }

    public function updatedCancelDate(AccountApiService $api): void
    {
        if ($this->showCancel) {
            $this->loadCancelPreview($api);
        }
    }

    protected function loadCancelPreview(AccountApiService $api): void
    {
        $this->cancelPreviewLoading = true;
        $result = $api->cancelSubscriptionInfo($this->cancelDate, $this->subscriptionId);
        $this->cancelPreviewLoading = false;

        if ($result['ok'] ?? false) {
            $data = $result['data'] ?? [];
            $this->cancelPreview = is_array($data) ? $data : [];
        } else {
            $this->cancelPreview = [];
        }
    }

    public function closeCancel(): void
    {
        $this->showCancel = false;
        $this->cancelPreview = [];
    }

    public function confirmCancel(AccountApiService $api): void
    {
        $this->error = $this->notice = '';
        $reason = trim($this->cancelReason);
        if ($reason === '') {
            $this->error = __('account.cancel_reason_required');

            return;
        }
        $date = $this->cancelDate ?: now()->format('Y-m-d');
        $result = $api->cancelSubscription($date, $reason, $this->subscriptionId);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }
        $this->showCancel = false;
        $this->notice = __('account.subscription_cancelled');
        $this->load($api);
    }

    public function openPause(): void
    {
        $this->showPause = true;
        $this->pausedDate = $this->focusDate ?: now()->format('Y-m-d');
        $this->reactivateDate = now()->addDays(7)->format('Y-m-d');
    }

    public function closePause(): void
    {
        $this->showPause = false;
    }

    public function confirmPause(AccountApiService $api): void
    {
        $this->error = $this->notice = '';
        if ($this->pausedDate === '' || $this->reactivateDate === '') {
            $this->error = __('account.invalid_input');

            return;
        }
        $result = $api->updateSubscriptionStatus(
            'paused',
            $this->pausedDate,
            $this->reactivateDate,
            $this->subscriptionId,
        );
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }
        $this->showPause = false;
        $this->notice = __('account.subscription_paused');
        $this->load($api);
    }

    public function resume(AccountApiService $api): void
    {
        $this->error = $this->notice = '';
        $result = $api->updateSubscriptionStatus('active', null, null, $this->subscriptionId);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }
        $this->notice = __('account.subscription_resumed');
        $this->load($api);
    }

    public function openReplace(AccountApiService $api, int $planMenuId, int $dietId, int $mealId, string $date): void
    {
        $this->error = $this->notice = '';
        $status = (string) ($this->subscription['status'] ?? '');
        if (! SubscriptionLifecycle::canReplaceMeal($status)) {
            $this->error = __('account.action_not_available');

            return;
        }
        $this->replacePlanMenuId = $planMenuId;
        $this->replaceDietId = $dietId;
        $this->replaceMealId = $mealId;
        $this->replaceDate = $date;
        $this->selectedReplaceDietId = null;
        $this->replaceOptions = [];
        $this->showReplace = true;
        $this->replaceLoading = true;

        $result = $api->getReplaceMealOptions($planMenuId, $date, $dietId, $mealId, $this->subscriptionId);
        $this->replaceLoading = false;

        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->showReplace = false;

            return;
        }

        $this->replaceOptions = $this->extractRows($result['data'] ?? null, ['meals', 'options', 'alternatives']);
    }

    public function closeReplace(): void
    {
        $this->showReplace = false;
        $this->replaceOptions = [];
    }

    public function confirmReplace(AccountApiService $api): void
    {
        $this->error = $this->notice = '';
        if (
            ! $this->replaceDietId || ! $this->replaceMealId
            || ! $this->selectedReplaceDietId || $this->replaceDate === ''
        ) {
            $this->error = __('account.select_replacement_meal');

            return;
        }

        $result = $api->replaceMeal(
            $this->replaceDate,
            $this->replaceDietId,
            $this->replaceMealId,
            $this->selectedReplaceDietId,
            $this->subscriptionId,
        );

        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }

        $this->showReplace = false;
        $this->notice = __('account.meal_replaced');
        $this->load($api);
    }

    public function render()
    {
        return view('livewire.account.subscriptions.show');
    }
}
