<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('طلباتي')]
class Index extends Component
{
    use NormalizesAccountPayload;

    #[Url(as: 'status')]
    public string $status = 'active';

    public array $orders = [];

    public bool $loading = true;

    public string $error = '';

    public function mount(AccountApiService $api): void
    {
        $this->reload($api);
    }

    public function updatedStatus(AccountApiService $api): void
    {
        $this->reload($api);
    }

    public function reload(AccountApiService $api): void
    {
        $allowed = ['active', 'completed', 'cancelled'];
        if (! in_array($this->status, $allowed, true)) {
            $this->status = 'active';
        }

        $this->loading = true;
        $this->error = '';
        $result = $api->listOrders($this->status);

        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->orders = [];
            $this->loading = false;
            return;
        }

        $this->orders = $this->extractRows($result['data'] ?? null, ['orders', 'items', 'rows']);
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.orders.index');
    }
}
