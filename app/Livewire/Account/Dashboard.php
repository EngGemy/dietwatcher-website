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
        $subscriptions = $this->extractRows($subs['data'] ?? null, ['subscriptions', 'items', 'rows']);
        $active = collect($subscriptions)->first(function (array $s): bool {
            $status = strtolower((string) ($s['status'] ?? $s['state'] ?? ''));

            return in_array($status, ['active', 'running', 'started', 'ongoing', 'current', 'نشط', 'فعال'], true);
        }) ?? ($subscriptions[0] ?? []);
        $this->activeSubscription = is_array($active) ? $active : [];

        $orders = $api->listOrders('active');
        $orderRows = $this->extractRows($orders['data'] ?? null, ['orders', 'items', 'rows']);
        if ($orderRows === []) {
            $fallbackOrders = $api->listOrders('');
            $orderRows = $this->extractRows($fallbackOrders['data'] ?? null, ['orders', 'items', 'rows']);
        }
        $this->recentOrders = array_slice($orderRows, 0, 5);

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

    /**
     * Normalize list payloads returned in different API shapes.
     *
     * @param  mixed  $data
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(mixed $data, array $keys = []): array
    {
        if (! is_array($data)) {
            return [];
        }

        // Plain list already.
        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        // Common wrappers: data/items/orders/subscriptions...
        $candidateKeys = array_merge(['data'], $keys);
        foreach ($candidateKeys as $key) {
            $v = $data[$key] ?? null;
            if (is_array($v)) {
                if (array_is_list($v)) {
                    return array_values(array_filter($v, 'is_array'));
                }
                if (isset($v['data']) && is_array($v['data']) && array_is_list($v['data'])) {
                    return array_values(array_filter($v['data'], 'is_array'));
                }
            }
        }

        // Single object, treat as one-row.
        return [$data];
    }

    public function render()
    {
        return view('livewire.account.dashboard');
    }
}
