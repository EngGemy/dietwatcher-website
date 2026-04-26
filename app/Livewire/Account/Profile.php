<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Services\AccountApiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('الملف الشخصي')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $mobile = '';

    public string $gender = 'male';

    public string $birthdate = '';

    public bool $loading = true;

    public string $error = '';

    public string $notice = '';

    public function mount(AccountApiService $api): void
    {
        $this->load($api);
    }

    public function load(AccountApiService $api): void
    {
        $this->loading = true;
        $this->error = '';
        $result = $api->getProfile();

        $data = $result['data'] ?? [];
        if (is_array($data)) {
            $p = $data['profile'] ?? $data['customer'] ?? $data;
            if (is_array($p)) {
                $this->name      = (string) ($p['name'] ?? '');
                $this->email     = (string) ($p['email'] ?? '');
                $this->mobile    = (string) ($p['mobile'] ?? $p['phone'] ?? '');
                $this->gender    = (string) ($p['gender'] ?? 'male');
                $this->birthdate = (string) ($p['brithdate'] ?? $p['birthdate'] ?? '');
            }
        }

        // Fall back to session profile if API didn't return data
        if ($this->name === '') {
            $sessionProfile = (array) session('external_api_profile', []);
            $this->name   = (string) ($sessionProfile['name']   ?? $this->name);
            $this->email  = (string) ($sessionProfile['email']  ?? $this->email);
            $this->mobile = (string) ($sessionProfile['mobile'] ?? $this->mobile);
        }

        $this->loading = false;
    }

    public function save(AccountApiService $api): void
    {
        $this->error = $this->notice = '';

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'gender' => ['required', 'in:male,female'],
            'birthdate' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $payload = array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'gender' => $this->gender,
            'brithdate' => $this->birthdate,
        ], fn ($v) => $v !== null && $v !== '');

        $result = $api->updateProfile($payload);
        if (! ($result['ok'] ?? false)) {
            $this->error = $result['message'] ?: __('account.save_failed');
            return;
        }

        // Refresh session-stored profile snippet for layout avatar/name
        $existing = (array) session('external_api_profile', []);
        session([
            'external_api_profile' => array_merge($existing, [
                'name' => $this->name,
                'email' => $this->email,
                'gender' => $this->gender,
            ]),
        ]);

        $this->notice = __('account.profile_saved');
    }

    public function render()
    {
        return view('livewire.account.profile');
    }
}
