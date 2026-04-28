<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('account.my_profile') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('account.profile_hint') }}</p>
    </div>

    @if($notice)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>
    @endif
    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>
    @endif

    <form wire:submit.prevent="save" class="acc-card">
        <div class="acc-card-head">{{ __('account.personal_info') }}</div>
        <div class="acc-card-body grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.full_name') }}</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border border-gray-200 p-2.5 text-sm focus:outline-none focus:border-blue-400" required />
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.email') }}</label>
                <input type="email" wire:model="email" class="w-full rounded-lg border border-gray-200 p-2.5 text-sm focus:outline-none focus:border-blue-400" />
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.phone') }}</label>
                <input type="tel" wire:model="mobile" dir="ltr" class="w-full rounded-lg border border-gray-200 p-2.5 text-sm bg-gray-50 cursor-not-allowed" disabled />
                <p class="text-xs text-gray-400 mt-1">{{ __('account.phone_change_hint') }}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.gender') }}</label>
                <select wire:model="gender" class="w-full rounded-lg border border-gray-200 p-2.5 text-sm focus:outline-none focus:border-blue-400">
                    <option value="male">{{ __('account.male') }}</option>
                    <option value="female">{{ __('account.female') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('account.birthdate') }}</label>
                <input type="date" wire:model="birthdate" class="w-full rounded-lg border border-gray-200 p-2.5 text-sm focus:outline-none focus:border-blue-400" />
                @error('birthdate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="p-5 border-t border-gray-100 flex justify-end gap-2">
            <button type="submit" class="acc-btn acc-btn--primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ __('account.save_changes') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </button>
        </div>
    </form>
</div>
