<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Services\AccountApiService;
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

    /** @var array<int, array{id: int, duration: string, durationText: string, time: string, label: string}> */
    public array $deliveryTimeSlots = [];

    public string $deliveryTimeLabel = '';

    public ?int $selectedRegionDurationId = null;

    public bool $savingAddress = false;

    /** @var array<int, int> */
    public array $lockedAddressIds = [];

    public function mount(ApiAuthService $auth, AccountApiService $account): void
    {
        $this->reload($auth, $account);
    }

    public function reload(ApiAuthService $auth, AccountApiService $account): void
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

        $subscriptions = [];
        $subsResult = $account->listSubscriptions();
        if (($subsResult['ok'] ?? false) && is_array($subsResult['data']['subscriptions'] ?? null)) {
            $subscriptions = $subsResult['data']['subscriptions'];
        }
        $this->lockedAddressIds = AddressCheckoutHelper::collectLockedAddressIdsFromSubscriptions($subscriptions);

        $rows = $auth->getAddresses($token, false, false);
        $this->addresses = AddressCheckoutHelper::markDeliverability($rows, $subscriptions);

        foreach ($this->addresses as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $addressId = (int) ($row['id'] ?? 0);
            if ($addressId <= 0) {
                continue;
            }
            $this->addresses[$index]['days'] = $auth->resolveAddressDeliveryDaysForDisplay($token, $addressId, $row);
            $enriched = AddressCheckoutHelper::enrichAddressDistrictDurations($row, $this->addresses);
            $districtId = AddressCheckoutHelper::districtId($enriched);
            $slots = $auth->findDistrictRegionDurations($token, $districtId, $addressId);
            if ($slots === []) {
                $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($enriched);
            }
            $this->addresses[$index]['delivery_time_slots'] = $slots;
            $this->addresses[$index]['delivery_time_label'] = $this->resolveDeliveryTimeLabel($enriched, $slots);
            $this->addresses[$index]['region_duration_id'] = AddressCheckoutHelper::firstRegionDurationId($enriched);
        }
        $this->loading = false;
    }

    public function openDeliveryDays(ApiAuthService $auth, int $addressId): void
    {
        $this->error = $this->notice = '';
        $token = (string) session('external_api_token', '');
        if ($token === '' || $addressId <= 0) {
            return;
        }

        $addr = collect($this->addresses)->firstWhere('id', $addressId)
            ?? collect($this->addresses)->firstWhere(fn ($a) => (int) ($a['id'] ?? 0) === $addressId);

        if (! is_array($addr)) {
            $addr = $auth->findAddressById($token, $addressId, false);
        }

        if (is_array($addr) && $this->addressIsLocked($addr)) {
            $this->error = __('address.cant_modify');

            return;
        }

        $this->editingAddressId = $addressId;
        $this->selectedDays = [];
        $this->deliveryTimeSlots = [];
        $this->deliveryTimeLabel = '';
        $this->selectedRegionDurationId = null;

        $this->selectedDays = $auth->resolveAddressDeliveryDaysForDisplay(
            $token,
            $addressId,
            is_array($addr) ? $addr : null,
        );

        if (is_array($addr)) {
            $districtId = AddressCheckoutHelper::districtId($addr);
            $this->deliveryTimeSlots = $auth->findDistrictRegionDurations($token, $districtId, $addressId);
            if ($this->deliveryTimeSlots === []) {
                $this->deliveryTimeSlots = AddressCheckoutHelper::resolveDeliveryTimeSlots($addr);
            }
            $this->deliveryTimeLabel = $this->resolveDeliveryTimeLabel($addr, $this->deliveryTimeSlots);
            $regionId = AddressCheckoutHelper::firstRegionDurationId($addr);
            if ($regionId > 0) {
                $this->selectedRegionDurationId = $regionId;
            } elseif (count($this->deliveryTimeSlots) === 1) {
                $this->selectedRegionDurationId = (int) ($this->deliveryTimeSlots[0]['id'] ?? 0);
            }
        }
    }

    public function selectDeliveryTime(int $slotId): void
    {
        if ($slotId <= 0) {
            return;
        }

        $this->selectedRegionDurationId = $slotId;
        foreach ($this->deliveryTimeSlots as $slot) {
            if ((int) ($slot['id'] ?? 0) === $slotId) {
                $this->deliveryTimeLabel = (string) ($slot['label'] ?? '');

                return;
            }
        }
    }

    public function toggleDay(int $day): void
    {
        if ($day < 1 || $day > 7) {
            return;
        }

        if (in_array($day, $this->selectedDays, true)) {
            $this->selectedDays = array_values(array_filter(
                $this->selectedDays,
                static fn (int $value): bool => $value !== $day,
            ));
        } else {
            $this->selectedDays[] = $day;
        }

        sort($this->selectedDays);
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  array<int, array{id: int, duration: string, durationText: string, time: string, label: string}>  $slots
     */
    protected function resolveDeliveryTimeLabel(array $address, array $slots = []): string
    {
        $regionId = AddressCheckoutHelper::firstRegionDurationId($address);
        if ($regionId > 0 && $slots !== []) {
            foreach ($slots as $slot) {
                if ((int) ($slot['id'] ?? 0) === $regionId) {
                    return (string) ($slot['label'] ?? '');
                }
            }
        }

        $nested = $address['region_duration'] ?? $address['regionDuration'] ?? null;
        if (is_array($nested)) {
            return AddressCheckoutHelper::regionDurationLabel($nested);
        }

        foreach (AddressCheckoutHelper::districtDurations($address) as $row) {
            if ((int) ($row['id'] ?? 0) === $regionId) {
                return AddressCheckoutHelper::regionDurationLabel($row);
            }
        }

        return '';
    }

    public function closeDeliveryDays(): void
    {
        $this->editingAddressId = null;
        $this->selectedDays = [];
        $this->deliveryTimeSlots = [];
        $this->deliveryTimeLabel = '';
        $this->selectedRegionDurationId = null;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    protected function addressIsLocked(array $address): bool
    {
        return AddressCheckoutHelper::isCantModify($address, $this->lockedAddressIds);
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

        $addr = collect($this->addresses)->firstWhere('id', $addressId)
            ?? collect($this->addresses)->firstWhere(fn ($a) => (int) ($a['id'] ?? 0) === $addressId);
        if (! is_array($addr)) {
            $addr = $auth->findAddressById($token, $addressId, false);
        }
        if (is_array($addr) && $this->addressIsLocked($addr)) {
            $this->error = __('address.cant_modify');

            return;
        }

        $result = $auth->updateAddressDeliveryDays($token, $addressId, $days);
        if (! ($result['_http_ok'] ?? false) && ! ($result['success'] ?? false)) {
            $this->error = (string) ($result['message'] ?? __('account.save_failed'));

            return;
        }

        $this->notice = __('account.delivery_days_saved');
        $this->closeDeliveryDays();
        $this->reload($auth, app(AccountApiService::class));
    }

    public function deleteAddress(ApiAuthService $auth, AccountApiService $account, int $addressId): void
    {
        $this->error = $this->notice = '';
        $token = (string) session('external_api_token', '');
        if ($token === '' || $addressId <= 0) {
            return;
        }

        $addr = collect($this->addresses)->firstWhere('id', $addressId)
            ?? collect($this->addresses)->firstWhere(fn ($a) => (int) ($a['id'] ?? 0) === $addressId);
        if (! is_array($addr)) {
            $addr = $auth->findAddressById($token, $addressId, false);
        }
        if (is_array($addr) && $this->addressIsLocked($addr)) {
            $this->error = __('address.cant_modify');

            return;
        }

        $result = $auth->deleteAddress($token, $addressId);
        if (! ($result['_http_ok'] ?? false) && ! ($result['success'] ?? false)) {
            $this->error = (string) ($result['message'] ?? __('account.action_failed'));

            return;
        }

        $this->notice = __('account.address_deleted');
        $this->reload($auth, $account);
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
            $this->reload($auth, app(AccountApiService::class));
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
        $this->reload($auth, app(AccountApiService::class));

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
