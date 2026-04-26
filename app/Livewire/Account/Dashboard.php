<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    public array $activeSubscription = [];

    public array $recentOrders = [];

    public ?float $walletBalance = null;

    public bool $loading = true;

    public function mount(AccountApiService $api): void
    {
        $subs = $api->listSubscriptions();
        $data = $subs['data'] ?? [];
        if (is_array($data)) {
            // Heuristic: find first subscription whose status resembles "active"/"running"
            $list = array_values(array_filter($data, 'is_array'));
            $active = collect($list)->first(function ($s) {
                $status = strtolower((string) ($s['status'] ?? $s['state'] ?? ''));
                return in_array($status, ['active', 'running', 'started'], true);
            }) ?? ($list[0] ?? []);
            $this->activeSubscription = is_array($active) ? $active : [];
        }

        $orders = $api->listOrders('active');
        $ordersData = $orders['data'] ?? [];
        if (is_array($ordersData)) {
            $rows = $ordersData['data'] ?? $ordersData;
            $this->recentOrders = is_array($rows) ? array_slice(array_values(array_filter($rows, 'is_array')), 0, 5) : [];
        }

        $wallet = $api->getWallet();
        $walletData = $wallet['data'] ?? [];
        if (is_array($walletData)) {
            $bal = $walletData['balance']
                ?? $walletData['wallet']['balance']
                ?? $walletData['total']
                ?? $walletData['amount']
                ?? null;
            if ($bal !== null) {
                $this->walletBalance = (float) $bal;
            }
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.dashboard');
    }
}
