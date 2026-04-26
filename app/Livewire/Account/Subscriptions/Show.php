<?php

declare(strict_types=1);

namespace App\Livewire\Account\Subscriptions;

use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('تفاصيل الاشتراك')]
class Show extends Component
{
    public int $subscriptionId = 0;

    public string $focusDate = '';

    public array $subscription = [];

    public array $days = [];

    public bool $loading = true;

    public string $error = '';

    public string $notice = '';

    // Cancel modal state
    public bool $showCancel = false;

    public string $cancelReason = '';

    // Pause modal state
    public bool $showPause = false;

    public string $pausedDate = '';

    public string $reactivateDate = '';

    public function mount(AccountApiService $api, int $id): void
    {
        $this->subscriptionId = $id;
        if ($this->focusDate === '') {
            $this->focusDate = now()->format('Y-m-d');
        }
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
        // Shape varies — try common paths
        $sub = [];
        $days = [];

        if (is_array($data)) {
            // Some APIs wrap the target subscription
            $sub = $data['subscription'] ?? $data['data'] ?? $data;

            // Days might be in subscription.days, data.days, or inside the subscription's menus
            $days = $sub['days']
                ?? $sub['menu_days']
                ?? $data['days']
                ?? [];

            if (! is_array($sub)) $sub = [];
            if (! is_array($days)) $days = [];

            // If $sub is a list of subscriptions, pick the one matching our id
            if (isset($sub[0]) && is_array($sub[0])) {
                $match = collect($sub)->first(fn ($s) => is_array($s) && (int) ($s['id'] ?? 0) === $this->subscriptionId);
                if ($match) $sub = $match;
            }
        }

        $this->subscription = $sub;
        $this->days = array_values(array_filter($days, 'is_array'));
        $this->loading = false;
    }

    public function skipDay(AccountApiService $api, int $dietId, string $date): void
    {
        $this->error = $this->notice = '';
        if ($dietId <= 0 || $date === '') {
            $this->error = __('account.invalid_input');
            return;
        }
        $result = $api->skipDay($dietId, $date, $date);
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
        $result = $api->restoreDay($dietId, $date);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');
            return;
        }
        $this->notice = __('account.day_restored');
        $this->load($api);
    }

    public function openCancel(): void
    {
        $this->showCancel = true;
        $this->cancelReason = '';
    }

    public function closeCancel(): void
    {
        $this->showCancel = false;
    }

    public function confirmCancel(AccountApiService $api): void
    {
        $this->error = $this->notice = '';
        $reason = trim($this->cancelReason);
        if ($reason === '') {
            $this->error = __('account.cancel_reason_required');
            return;
        }
        $date = $this->focusDate ?: now()->format('Y-m-d');
        $result = $api->cancelSubscription($date, $reason);
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
        $result = $api->updateSubscriptionStatus('paused', $this->pausedDate, $this->reactivateDate);
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
        $result = $api->updateSubscriptionStatus('active');
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');
            return;
        }
        $this->notice = __('account.subscription_resumed');
        $this->load($api);
    }

    public function render()
    {
        return view('livewire.account.subscriptions.show');
    }
}
