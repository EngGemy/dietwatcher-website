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

        $this->subscriptions = $this->extractRows($result['data'] ?? null, ['subscriptions', 'items', 'rows']);

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
        return view('livewire.account.subscriptions.index');
    }
}
