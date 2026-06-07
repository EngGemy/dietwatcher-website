<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Services\ApiAuthService;
use App\Support\AddressCheckoutHelper;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.account')]
#[Title('عناويني')]
class Addresses extends Component
{
    public array $addresses = [];

    public bool $loading = true;

    public string $error = '';

    public string $notice = '';

    public ?int $editingAddressId = null;

    /** @var array<int, int> */
    public array $selectedDays = [];

    public bool $savingAddress = false;

    public function mount(ApiAuthService $auth): void
    {
        $this->reload($auth);
    }

    public function reload(ApiAuthService $auth): void
    {
        $this->loading = true;
        $this->error = '';
        $token = (string) session('external_api_token', '');
        if ($token === '') {
            $this->error = __('account.login_required');
            $this->addresses = [];
            $this->loading = false;

            return;
        }

        $this->addresses = $auth->getAddresses($token, false, false);
        $this->loading = false;
    }

    public function openDeliveryDays(ApiAuthService $auth, int $addressId): void
    {
        $this->error = $this->notice = '';
        $token = (string) session('external_api_token', '');
        if ($token === '' || $addressId <= 0) {
            return;
        }

        $this->editingAddressId = $addressId;
        $this->selectedDays = [];

        $addr = collect($this->addresses)->firstWhere('id', $addressId)
            ?? collect($this->addresses)->firstWhere(fn ($a) => (int) ($a['id'] ?? 0) === $addressId);

        if (is_array($addr) && ! empty($addr['days']) && is_array($addr['days'])) {
            $this->selectedDays = array_map('intval', array_values($addr['days']));
        } else {
            $resp = $auth->getAddressDeliveryDays($token, $addressId);
            $days = $resp['data']['days'] ?? $resp['days'] ?? $resp['data'] ?? [];
            if (is_array($days)) {
                $this->selectedDays = array_map('intval', array_values($days));
            }
        }
    }

    public function closeDeliveryDays(): void
    {
        $this->editingAddressId = null;
        $this->selectedDays = [];
    }

    public function saveDeliveryDays(ApiAuthService $auth): void
    {
        $this->error = $this->notice = '';
        $token = (string) session('external_api_token', '');
        $addressId = (int) ($this->editingAddressId ?? 0);
        if ($token === '' || $addressId <= 0) {
            $this->error = __('account.invalid_input');

            return;
        }

        $days = array_values(array_unique(array_map('intval', $this->selectedDays)));
        sort($days);

        if ($days === []) {
            $this->error = __('account.delivery_days_required');

            return;
        }

        $result = $auth->updateAddressDeliveryDays($token, $addressId, $days);
        if (! ($result['_http_ok'] ?? false) && ! ($result['success'] ?? false)) {
            $this->error = (string) ($result['message'] ?? __('account.save_failed'));

            return;
        }

        $this->notice = __('account.delivery_days_saved');
        $this->closeDeliveryDays();
        $this->reload($auth);
    }

    public function deleteAddress(ApiAuthService $auth, int $addressId): void
    {
        $this->error = $this->notice = '';
        $token = (string) session('external_api_token', '');
        if ($token === '' || $addressId <= 0) {
            return;
        }

        $result = $auth->deleteAddress($token, $addressId);
        if (! ($result['_http_ok'] ?? false) && ! ($result['success'] ?? false)) {
            $this->error = (string) ($result['message'] ?? __('account.action_failed'));

            return;
        }

        $this->notice = __('account.address_deleted');
        $this->reload($auth);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveNewAddress(ApiAuthService $auth, array $payload): void
    {
        $this->error = $this->notice = '';
        $this->savingAddress = true;

        $token = (string) session('external_api_token', '');
        if ($token === '') {
            $this->error = __('account.login_required');
            $this->savingAddress = false;

            return;
        }

        $lat = (float) ($payload['latitude'] ?? 0);
        $lng = (float) ($payload['longitude'] ?? 0);
        $districtId = (int) ($payload['district_id'] ?? 0);
        $description = trim((string) ($payload['description'] ?? ''));
        $buildingNotes = trim((string) ($payload['building_notes'] ?? ''));
        $fullDesc = $description;
        if ($buildingNotes !== '') {
            $fullDesc = $description === '' ? $buildingNotes : $description."\n".$buildingNotes;
        }

        $uiType = (string) ($payload['type'] ?? 'home');
        $title = match ($uiType) {
            'work' => 'Office',
            'other' => trim((string) ($payload['title'] ?? '')) !== ''
                ? trim((string) $payload['title'])
                : 'Other',
            default => 'Home',
        };

        $apiType = match ($uiType) {
            'work' => 'commercial',
            'other' => 'other',
            default => 'residential',
        };

        $pickup = (string) ($payload['pickup_type'] ?? 'hand_it_to_me');
        if (! in_array($pickup, ['hand_it_to_me', 'leave_at_door'], true)) {
            $pickup = 'hand_it_to_me';
        }

        if ($lat === 0.0 || $lng === 0.0 || $districtId <= 0 || $fullDesc === '') {
            $this->error = __('account.invalid_input');
            $this->savingAddress = false;

            return;
        }

        if ($uiType === 'other' && trim((string) ($payload['title'] ?? '')) === '') {
            $this->error = __('account.address_label_required');
            $this->savingAddress = false;

            return;
        }

        $apiPayload = [
            'title' => $title,
            'latitude' => (string) $lat,
            'longitude' => (string) $lng,
            'description' => $fullDesc,
            'type' => $apiType,
            'district_id' => (string) $districtId,
            'pickup_type' => $pickup,
        ];

        $existing = $auth->getAddresses($token, true, true);
        $duplicate = AddressCheckoutHelper::findDuplicate($existing, array_merge($apiPayload, [
            'district_id' => $districtId,
            'latitude' => $lat,
            'longitude' => $lng,
        ]));
        if ($duplicate !== null) {
            $this->notice = __('account.address_already_saved');
            $this->reload($auth);
            $this->openDeliveryDays($auth, (int) ($duplicate['id'] ?? 0));
            $this->savingAddress = false;

            return;
        }

        $result = $auth->storeAddress($token, $apiPayload);
        $httpOk = (bool) ($result['_http_ok'] ?? false);
        $apiStatus = (int) ($result['status'] ?? 0);
        $hasData = array_key_exists('data', $result);

        if (! $httpOk || ($apiStatus !== 200 && ! $hasData)) {
            $this->error = (string) ($result['message'] ?? __('account.save_failed'));
            $this->savingAddress = false;

            return;
        }

        $stored = AddressCheckoutHelper::unwrapStoredAddress($result['data'] ?? null);
        $storedId = (int) ($stored['id'] ?? 0);
        $this->notice = __('account.address_saved');
        $this->reload($auth);

        if ($storedId > 0) {
            $this->openDeliveryDays($auth, $storedId);
        }

        $this->savingAddress = false;
    }

    public function render()
    {
        return view('livewire.account.addresses');
    }
}
