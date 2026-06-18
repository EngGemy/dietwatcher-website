<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use App\Services\ApiAuthService;
use App\Services\DailyHealthTipsService;
use App\Support\AddressCheckoutHelper;
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

    public int $savedAddressesCount = 0;

    public int $lockedAddressesCount = 0;

    /** @var array<int, string> */
    public array $dailyTips = [];

    public bool $loading = true;

    public string $error = '';

    public function mount(
        AccountApiService $api,
        ApiAuthService $auth,
        DailyHealthTipsService $tips,
    ): void {
        $this->loading = true;
        $this->error = '';

        $token = (string) session('external_api_token', '');
        $subscriptions = [];

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
                'pending',
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

        $wallet = $api->getWallet('all', null, null, 1);
        if ($wallet['ok'] ?? false) {
            $this->walletBalance = $this->extractWalletBalance($wallet['data'] ?? null)
                ?? $this->extractWalletBalance(is_array($wallet['raw'] ?? null) ? $wallet['raw'] : null);
        }

        if ($this->walletBalance === null) {
            $profile = session('external_api_profile', []);
            if (is_array($profile) && $profile !== []) {
                $this->walletBalance = $this->extractWalletBalance($profile);
            }
        }

        if ($this->walletBalance === null) {
            $profileResult = $api->getProfile();
            if ($profileResult['ok'] ?? false) {
                $this->walletBalance = $this->extractWalletBalance($profileResult['data'] ?? null)
                    ?? $this->extractWalletBalance(is_array($profileResult['raw'] ?? null) ? $profileResult['raw'] : null);
            }
        }

        $this->unreadNotifications = $api->unreadNotificationCount();

        if ($token !== '') {
            $addresses = AddressCheckoutHelper::markDeliverability(
                $auth->getAddresses($token, true, false),
                $subscriptions,
            );
            $this->savedAddressesCount = count($addresses);
            $this->lockedAddressesCount = count(array_filter(
                $addresses,
                static fn (array $row): bool => AddressCheckoutHelper::isCantModify($row),
            ));
        }

        $this->dailyTips = $tips->tips();

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
