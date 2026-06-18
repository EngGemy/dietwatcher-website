<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('محفظتي')]
class Wallet extends Component
{
    use NormalizesAccountPayload;

    #[Url(as: 'type')]
    public string $type = 'all';

    public ?float $balance = null;

    public array $transactions = [];

    public float $creditsTotal = 0.0;

    public float $debitsTotal = 0.0;

    public int $transactionsTotal = 0;

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
        $this->creditsTotal = 0.0;
        $this->debitsTotal = 0.0;

        $allowed = ['all', 'charge', 'sale'];
        if (! in_array($this->type, $allowed, true)) {
            $this->type = 'all';
        }

        $this->balance = $this->resolveWalletBalance($api);

        $result = $api->getWallet($this->type, null, null, $this->page);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->transactions = [];
            $this->loading = false;

            return;
        }

        $data = $result['data'] ?? [];
        if (is_array($data)) {
            if ($this->balance === null) {
                $this->balance = $this->extractWalletBalance($data)
                    ?? $this->extractWalletBalance(is_array($result['raw'] ?? null) ? $result['raw'] : null);
            }

            $this->transactions = $this->extractRows($data, ['transactions', 'wallet_transactions', 'items', 'rows']);
        }

        $summary = $this->summarizeWalletTransactions($api);
        $this->transactionsTotal = $summary['count'];
        if ($this->balance === null) {
            $this->balance = $summary['balance'];
        }
        $this->creditsTotal = $summary['credits'];
        $this->debitsTotal = $summary['debits'];

        foreach ($this->transactions as $index => $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $amount = $this->extractTransactionAmount($transaction) ?? 0.0;
            $isCredit = $this->walletTransactionIsCredit($transaction, $amount);
            $signedAmount = $isCredit ? abs($amount) : -abs($amount);

            $this->transactions[$index]['_label'] = $this->walletTransactionLabel($transaction);
            $this->transactions[$index]['_is_credit'] = $isCredit;
            $this->transactions[$index]['_amount'] = $signedAmount;
            $this->transactions[$index]['_when'] = $this->formatWalletDate(
                (string) ($transaction['created_at'] ?? $transaction['date'] ?? $transaction['createdAt'] ?? '')
            );
        }

        $this->loading = false;
    }

    protected function resolveWalletBalance(AccountApiService $api): ?float
    {
        $profile = session('external_api_profile', []);
        if (is_array($profile) && $profile !== []) {
            $fromSession = $this->extractWalletBalance($profile);
            if ($fromSession !== null) {
                return $fromSession;
            }
        }

        $profileResult = $api->getProfile();
        if ($profileResult['ok'] ?? false) {
            $fromProfile = $this->extractWalletBalance($profileResult['data'] ?? null)
                ?? $this->extractWalletBalance(is_array($profileResult['raw'] ?? null) ? $profileResult['raw'] : null);
            if ($fromProfile !== null) {
                return $fromProfile;
            }
        }

        $balanceResult = $api->getWallet('all', null, null, 1);
        if ($balanceResult['ok'] ?? false) {
            return $this->extractWalletBalance($balanceResult['data'] ?? null)
                ?? $this->extractWalletBalance(is_array($balanceResult['raw'] ?? null) ? $balanceResult['raw'] : null);
        }

        return null;
    }

    /**
     * @return array{balance: ?float, credits: float, debits: float, count: int}
     */
    protected function summarizeWalletTransactions(AccountApiService $api): array
    {
        $result = $api->getWallet('all', null, null, 1);
        if (! ($result['ok'] ?? false)) {
            return [
                'balance' => null,
                'credits' => 0.0,
                'debits' => 0.0,
                'count' => count($this->transactions),
            ];
        }

        $data = $result['data'] ?? [];
        $rows = is_array($data)
            ? $this->extractRows($data, ['transactions', 'wallet_transactions', 'items', 'rows'])
            : [];

        if ($rows === [] && is_array($data) && array_is_list($data)) {
            $rows = array_values(array_filter($data, 'is_array'));
        }

        $balance = $this->extractWalletBalance($data)
            ?? $this->extractWalletBalance(is_array($result['raw'] ?? null) ? $result['raw'] : null);

        $credits = 0.0;
        $debits = 0.0;

        foreach ($rows as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $amount = $this->extractTransactionAmount($transaction);
            if ($amount === null) {
                continue;
            }

            if ($this->walletTransactionIsCredit($transaction, $amount)) {
                $credits += abs($amount);
            } else {
                $debits += abs($amount);
            }
        }

        return [
            'balance' => $balance,
            'credits' => round($credits, 2),
            'debits' => round($debits, 2),
            'count' => count($rows),
        ];
    }

    protected function formatWalletDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('d M Y · h:i A');
        } catch (\Throwable) {
            return $value;
        }
    }

    public function render()
    {
        return view('livewire.account.wallet');
    }
}
