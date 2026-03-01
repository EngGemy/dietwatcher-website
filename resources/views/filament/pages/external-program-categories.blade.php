<x-filament-panels::page>
    @php
        $categories = $this->getCategories();
        $locale = app()->getLocale();

        $resolveName = function ($name) use ($locale) {
            if (is_array($name)) {
                return $name[$locale] ?? $name['en'] ?? array_values($name)[0] ?? '—';
            }
            return $name ?: '—';
        };
    @endphp

    @if(empty($categories))
        <x-filament::section>
            <div class="text-center py-8 text-gray-500">
                <p class="font-semibold text-lg">{{ __('admin.external.categories.empty') }}</p>
                <p class="text-sm mt-1">{{ __('admin.external.categories.empty_desc') }}</p>
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
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.description') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">{{ __('admin.external.programs.navigation_label') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.badges') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($categories as $category)
                                @php $catName = $resolveName($category['name']); @endphp
                                <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 font-mono">{{ $category['id'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(!empty($category['image_url']))
                                            <img src="{{ $category['image_url'] }}" alt="" class="w-8 h-8 rounded object-cover" />
                                        @else
                                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-800"></div>
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $catName }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $category['description'] ?: '—' }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-center">
                                        @if(!empty($category['programs_count']))
                                            <x-filament::badge color="success" size="sm">{{ $category['programs_count'] }}</x-filament::badge>
                                        @else
                                            <span class="text-sm text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(!empty($category['badge']['name']))
                                            <x-filament::badge color="warning" size="sm">{{ $category['badge']['name'] }}</x-filament::badge>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="fi-ta-footer px-3 py-2 text-sm text-gray-500 border-t border-gray-200 dark:border-white/5">
                    {{ __('admin.external.total') }}: <strong>{{ count($categories) }}</strong>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
