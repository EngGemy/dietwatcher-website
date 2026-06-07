<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('الإشعارات')]
class Notifications extends Component
{
    use NormalizesAccountPayload;

    public array $items = [];

    public int $page = 1;

    public bool $hasMore = false;

    public int $unreadCount = 0;

    public bool $loading = true;

    public string $error = '';

    public string $notice = '';

    public function mount(AccountApiService $api): void
    {
        $this->unreadCount = $api->unreadNotificationCount();
        $this->loadPage($api, 1, true);
    }

    public function loadPage(AccountApiService $api, int $page, bool $replace = false): void
    {
        $this->loading = true;
        $this->error = '';

        $result = $api->notifications($page);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.load_failed');
            if ($replace) {
                $this->items = [];
            }
            $this->loading = false;

            return;
        }

        $data = $result['data'] ?? [];
        $rows = $this->extractRows($data, ['notifications', 'items', 'rows']);
        if ($rows === [] && is_array($data) && array_is_list($data)) {
            $rows = array_values(array_filter($data, 'is_array'));
        }

        $this->items = $replace ? $rows : array_merge($this->items, $rows);
        $this->page = $page;
        $this->hasMore = count($rows) >= 15;
        $this->loading = false;
    }

    public function loadMore(AccountApiService $api): void
    {
        if (! $this->hasMore || $this->loading) {
            return;
        }
        $this->loadPage($api, $this->page + 1, false);
    }

    public function markAllRead(AccountApiService $api): void
    {
        $this->error = $this->notice = '';
        $result = $api->markNotificationsRead();
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.action_failed');

            return;
        }
        $this->unreadCount = 0;
        $this->notice = __('account.notifications_marked_read');
        $this->loadPage($api, 1, true);
    }

    public function refresh(AccountApiService $api): void
    {
        $this->unreadCount = $api->unreadNotificationCount();
        $this->loadPage($api, 1, true);
    }

    public function render()
    {
        return view('livewire.account.notifications');
    }
}
