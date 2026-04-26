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
            $this->balance = $this->extractAmount($data);
            $this->transactions = $this->extractRows($data, ['transactions', 'wallet_transactions', 'items', 'rows']);
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.wallet');
    }
}
