<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('محفظتي')]
class Wallet extends Component
{
    #[Url(as: 'type')]
    public string $type = 'all';

    public ?float $balance = null;

    public array $transactions = [];

    public int $page = 1;

    public bool $loading = true;

    public string $error = '';

    public function mount(AccountApiService $api): void
    {
        $this->load($api);
    }

    public function updatedType(AccountApiService $api): void
    {
        $this->page = 1;
        $this->load($api);
    }

    public function load(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';

        $allowed = ['all', 'charge', 'sale'];
        if (! in_array($this->type, $allowed, true)) {
            $this->type = 'all';
        }

        $result = $api->getWallet($this->type, null, null, $this->page);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->balance = null;
            $this->transactions = [];
            $this->loading = false;
            return;
        }

        $data = $result['data'] ?? [];
        if (is_array($data)) {
            $bal = $data['balance']
                ?? $data['wallet_balance']
                ?? ($data['wallet']['balance'] ?? null)
                ?? $data['total']
                ?? $data['amount']
                ?? null;
            $this->balance = $bal !== null ? (float) $bal : null;
            $this->transactions = $this->extractRows($data, ['transactions', 'wallet_transactions', 'items', 'rows']);
        }

        $this->loading = false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(array $data, array $keys = []): array
    {
        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        $candidateKeys = array_merge(['data', 'response'], $keys);
        foreach ($candidateKeys as $key) {
            $v = $data[$key] ?? null;
            if (! is_array($v)) {
                continue;
            }

            if (array_is_list($v)) {
                return array_values(array_filter($v, 'is_array'));
            }

            if (isset($v['data']) && is_array($v['data']) && array_is_list($v['data'])) {
                return array_values(array_filter($v['data'], 'is_array'));
            }
        }

        return [];
    }

    public function render()
    {
        return view('livewire.account.wallet');
    }
}
