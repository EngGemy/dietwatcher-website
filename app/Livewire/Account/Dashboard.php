<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    use NormalizesAccountPayload;

    public array $activeSubscription = [];

    public array $recentOrders = [];

    public ?float $walletBalance = null;

    public int $unreadNotifications = 0;

    public bool $loading = true;

    public string $error = '';

    public function mount(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';

        $subs = $api->listSubscriptions();
        $subscriptions = $this->extractRowsFromApiResult(
            is_array($subs) ? $subs : [],
            ['subscriptions'],
            ['subscription'],
        );
        $active = collect($subscriptions)->first(function (array $s): bool {
            $status = strtolower((string) ($s['status'] ?? $s['state'] ?? ''));

            return in_array($status, [
                'active', 'running', 'started', 'ongoing', 'current',
                'paused', 'pausing', 'hold', 'on_hold',
                'نشط', 'فعال', 'paused',
            ], true);
        }) ?? ($subscriptions[0] ?? []);
        $this->activeSubscription = is_array($active) ? $active : [];

        $orders = $api->listOrders('active');
        $orderRows = ($orders['ok'] ?? false)
            ? $this->extractRowsFromApiResult($orders, ['orders'], ['order'])
            : [];
        if ($orderRows === []) {
            $fallbackOrders = $api->listOrders('');
            if ($fallbackOrders['ok'] ?? false) {
                $orderRows = $this->extractRowsFromApiResult($fallbackOrders, ['orders'], ['order']);
            }
        }
        $this->recentOrders = array_slice($orderRows, 0, 5);

        $wallet = $api->getWallet();
        $walletData = (($wallet['ok'] ?? false) && is_array($wallet['data'] ?? null))
            ? ($wallet['data'] ?? [])
            : [];
        if ($walletData !== []) {
            $this->walletBalance = $this->extractAmount($walletData);
        }

        $this->unreadNotifications = $api->unreadNotificationCount();

        if ($this->activeSubscription === [] && $this->recentOrders === [] && $this->walletBalance === null) {
            $this->error = ($subs['message'] ?? '') ?: ($orders['message'] ?? '') ?: ($wallet['message'] ?? '') ?: __('account.load_failed');
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.dashboard');
    }
}
