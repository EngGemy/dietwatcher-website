<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('تفاصيل الطلب')]
class Show extends Component
{
    use NormalizesAccountPayload;

    public int $orderId = 0;

    public array $order = [];

    public array $trackings = [];

    public bool $loading = true;

    public string $error = '';

    public function mount(AccountApiService $api, int $id): void
    {
        $this->orderId = $id;
        $this->load($api);
    }

    public function load(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';

        $result = $api->showOrder($this->orderId);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            $this->order = [];
            $this->loading = false;
            return;
        }

        $data = $result['data'] ?? [];
        $this->order = $this->extractOne($data, ['order']);

        $track = $api->orderTrackings($this->orderId);
        $this->trackings = $this->extractRows($track['data'] ?? null, ['trackings', 'items', 'rows']);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.account.orders.show', [
            'invoiceUrl' => app(AccountApiService::class)->orderInvoicePdfUrl($this->orderId),
        ]);
    }
}
