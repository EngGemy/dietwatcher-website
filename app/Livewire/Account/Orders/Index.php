<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('طلباتي')]
class Index extends Component
{
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

    /**
     * @param  mixed  $data
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(mixed $data, array $keys = []): array
    {
        if (! is_array($data)) {
            return [];
        }

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
        return view('livewire.account.orders.index');
    }
}
