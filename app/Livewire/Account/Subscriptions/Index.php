<?php

declare(strict_types=1);

namespace App\Livewire\Account\Subscriptions;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('اشتراكاتي')]
class Index extends Component
{
    use NormalizesAccountPayload;

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

        $this->subscriptions = $this->filterValidRows(
            $this->extractRowsFromApiResult(
                $result,
                ['subscriptions'],
                ['subscription'],
            ),
            fn (array $row): bool => (int) ($row['id'] ?? 0) > 0
                || trim((string) ($row['plan']['name'] ?? $row['program']['name'] ?? $row['name'] ?? '')) !== '',
        );

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.subscriptions.index');
    }
}
