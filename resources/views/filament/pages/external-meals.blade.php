<x-filament-panels::page>
    @php
        $groups = $this->getMealGroups();
        $mealsData = $this->getMealsData();
        $meals = $mealsData['data'] ?? [];
        $meta = $mealsData['meta'] ?? [];
        $currentPage = (int) ($meta['currentPage'] ?? $this->page);
        $lastPage = (int) ($meta['lastPage'] ?? 1);
    @endphp

    {{-- Group Filter --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <button wire:click="filterByGroup(null)" @class(['px-3 py-1.5 rounded-full text-sm font-medium transition-colors', 'bg-primary-600 text-white' => $this->groupId === null, 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' => $this->groupId !== null])>
            {{ __('admin.external.meals.all_groups') }}
        </button>
        @foreach($groups as $group)
            <button wire:click="filterByGroup({{ $group['value'] }})" @class(['px-3 py-1.5 rounded-full text-sm font-medium transition-colors flex items-center gap-1.5', 'bg-primary-600 text-white' => $this->groupId === $group['value'], 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' => $this->groupId !== $group['value']])>
                @if(!empty($group['icon']))<img src="{{ $group['icon'] }}" alt="" class="w-4 h-4 rounded-full object-cover" />@endif
                {{ $group['name'] }}
            </button>
        @endforeach
    </div>

    @if(empty($meals))
        <x-filament::section>
            <div class="text-center py-8 text-gray-500">
                <p class="font-semibold text-lg">{{ __('admin.external.meals.empty') }}</p>
                <p class="text-sm mt-1">{{ __('admin.external.meals.empty_desc') }}</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="fi-ta">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5 text-start">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">ID</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.image') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.name') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-end">{{ __('admin.external.programs.price') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">Tags</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">{{ __('admin.external.meals.ingredients') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($meals as $idx => $meal)
                                <tr wire:click="selectMeal({{ $idx }})" class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 font-mono">{{ $meal['id'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(!empty($meal['image_url']))
                                            <img src="{{ $meal['image_url'] }}" alt="" class="w-8 h-8 rounded object-cover" />
                                        @else
                                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-800"></div>
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $meal['name'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-gray-950 dark:text-white">
                                        @if($meal['offer_price'] && $meal['offer_price'] > 0)
                                            {{ number_format($meal['offer_price'], 0) }} SAR
                                        @else
                                            {{ number_format($meal['price'], 0) }} SAR
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(array_slice($meal['tags'] ?? [], 0, 2) as $tag)
                                                <x-filament::badge color="info" size="sm">{{ $tag['name'] }}</x-filament::badge>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3 text-center text-sm text-gray-500">{{ count($meal['ingredients'] ?? []) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($lastPage > 1)
                    <div class="fi-ta-footer px-3 py-3 border-t border-gray-200 dark:border-white/5">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">{{ __('admin.external.meals.page') }} {{ $currentPage }} / {{ $lastPage }}</div>
                            <div class="flex items-center gap-1">
                                <x-filament::button wire:click="previousPage" :disabled="$currentPage <= 1" color="gray" size="sm">{{ __('admin.external.meals.previous') }}</x-filament::button>
                                @for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++)
                                    <button wire:click="goToPage({{ $i }})" @class(['w-8 h-8 rounded-lg text-sm font-medium', 'bg-primary-600 text-white' => $i === $currentPage, 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' => $i !== $currentPage])>{{ $i }}</button>
                                @endfor
                                <x-filament::button wire:click="nextPage" :disabled="$currentPage >= $lastPage" color="gray" size="sm">{{ __('admin.external.meals.next') }}</x-filament::button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @endif

    {{-- Detail Infolist --}}
    @if($this->selectedMeal)
        @php $m = $this->selectedMeal; @endphp
        <x-filament::section icon="heroicon-o-information-circle" :heading="$m['name']" :description="'ID: ' . $m['id']" collapsible>
            <div class="flex justify-end mb-2">
                <x-filament::button color="gray" size="sm" wire:click="closeDetail">{{ __('Close') }}</x-filament::button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Left --}}
                <div class="space-y-4">
                    @if(!empty($m['image_url']))
                        <img src="{{ $m['image_url'] }}" alt="" class="w-full max-w-xs rounded-lg object-cover" />
                    @endif

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">{{ __('admin.external.programs.price') }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ number_format($m['price'], 0) }} SAR</dd>

                        @if($m['offer_price'] && $m['offer_price'] > 0)
                            <dt class="text-gray-500">{{ __('admin.external.programs.offer_price') }}</dt>
                            <dd class="font-medium text-success-600">{{ number_format($m['offer_price'], 0) }} SAR</dd>
                        @endif

                        @if($m['rate'])
                            <dt class="text-gray-500">Rating</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $m['rate'] }} / 5</dd>
                        @endif
                    </dl>
                </div>

                {{-- Right --}}
                <div class="space-y-4">
                    @if(!empty($m['description']))
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('admin.external.programs.description') }}</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300">{{ $m['description'] }}</dd>
                        </div>
                    @endif

                    @if(!empty($m['tags']))
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">Tags</dt>
                            <div class="flex flex-wrap gap-1">
                                @foreach($m['tags'] as $tag)
                                    <x-filament::badge color="info" size="sm">{{ $tag['name'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($m['ingredients']))
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('admin.external.meals.ingredients') }} ({{ count($m['ingredients']) }})</dt>
                            <div class="flex flex-wrap gap-1">
                                @foreach($m['ingredients'] as $ing)
                                    <x-filament::badge color="gray" size="sm">{{ $ing['name'] ?? $ing }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($m['categories']))
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('admin.external.programs.category') }}</dt>
                            <div class="flex flex-wrap gap-1">
                                @foreach($m['categories'] as $cat)
                                    <x-filament::badge color="primary" size="sm">{{ $cat['name'] ?? $cat }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
