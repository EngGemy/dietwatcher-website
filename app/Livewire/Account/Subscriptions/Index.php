<?php

declare(strict_types=1);

namespace App\Livewire\Account\Subscriptions;

use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('اشتراكاتي')]
class Index extends Component
{
    public array $subscriptions = [];

    public bool $loading = true;

    public string $error = '';

    public function mount(AccountApiService $api): void
    {
        $this->reload($api);
    }

    public function reload(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';
        $result = $api->listSubscriptions();

        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->subscriptions = [];
            $this->loading = false;
            return;
        }

        $data = $result['data'] ?? [];
        // Shape can be { data: [...] } or raw array
        if (is_array($data)) {
            $rows = $data['data'] ?? $data;
            $this->subscriptions = is_array($rows)
                ? array_values(array_filter($rows, 'is_array'))
                : [];
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.subscriptions.index');
    }
}
