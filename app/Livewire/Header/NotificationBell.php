<?php

declare(strict_types=1);

namespace App\Livewire\Header;

use App\Services\AccountApiService;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public bool $visible = false;

    public function mount(AccountApiService $api): void
    {
        $token = (string) session('external_api_token', '');
        $phone = (string) session('phone_verified', '');
        $this->visible = $token !== '' && $phone !== '';

        if ($this->visible) {
            $this->unreadCount = $api->unreadNotificationCount();
        }
    }

    public function render()
    {
        return view('livewire.header.notification-bell');
    }
}
