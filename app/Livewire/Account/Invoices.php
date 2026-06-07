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
#[Title('الفواتير')]
class Invoices extends Component
{
    use NormalizesAccountPayload;

    #[Url(as: 'subscription')]
    public ?int $subscriptionFilter = null;

    public array $invoices = [];

    public bool $loading = true;

    public string $error = '';

    public function mount(AccountApiService $api): void
    {
        $this->load($api);
    }

    public function updatedSubscriptionFilter(AccountApiService $api): void
    {
        $this->load($api);
    }

    public function load(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';

        $result = $api->listInvoices($this->subscriptionFilter);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->invoices = [];
            $this->loading = false;

            return;
        }

        $this->invoices = $this->extractRows($result['data'] ?? null, ['invoices', 'items', 'rows']);
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.invoices', [
            'api' => app(AccountApiService::class),
        ]);
    }
}
