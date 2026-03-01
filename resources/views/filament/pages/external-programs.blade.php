<x-filament-panels::page>
    @php
        $programs = $this->getFilteredPrograms();
        $allPrograms = $this->getPrograms();
        $categories = $this->getCategories();
        $locale = app()->getLocale();

        $resolveName = function ($name) use ($locale) {
            if (is_array($name)) {
                return $name[$locale] ?? $name['en'] ?? array_values($name)[0] ?? '—';
            }
            return $name ?: '—';
        };

        $avgPrice = count($allPrograms) > 0 ? (int) collect($allPrograms)->avg('price') : 0;
        $avgDuration = count($allPrograms) > 0 ? (int) collect($allPrograms)->avg('duration_days') : 0;
    @endphp

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ count($allPrograms) }}</div>
                <div class="text-sm text-gray-500">{{ __('admin.external.programs.total_programs') }}</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-success-600">{{ count($categories) }}</div>
                <div class="text-sm text-gray-500">{{ __('admin.external.programs.category') }}</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-warning-600">{{ number_format($avgPrice) }} SAR</div>
                <div class="text-sm text-gray-500">{{ __('admin.external.programs.avg_price') }}</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-info-600">{{ $avgDuration }} {{ __('admin.external.programs.days') }}</div>
                <div class="text-sm text-gray-500">{{ __('admin.external.programs.avg_duration') }}</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Category Filter --}}
    @if(!empty($categories))
        <div class="flex flex-wrap gap-2 mb-4">
            <button wire:click="filterByCategory()" @class(['px-3 py-1.5 rounded-full text-sm font-medium transition-colors', 'bg-primary-600 text-white' => empty($this->selectedCategory), 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' => !empty($this->selectedCategory)])>
                {{ __('All') }} ({{ count($allPrograms) }})
            </button>
            @foreach($categories as $category)
                @php $catName = $resolveName($category['name']); $catId = (int) $category['id']; @endphp
                <button wire:click="filterByCategory({{ $catId }})" @class(['px-3 py-1.5 rounded-full text-sm font-medium transition-colors', 'bg-primary-600 text-white' => $this->selectedCategory === $catId, 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' => $this->selectedCategory !== $catId])>
                    {{ $catName }} @if(!empty($category['programs_count']))({{ $category['programs_count'] }})@endif
                </button>
            @endforeach
        </div>
    @endif

    @if(empty($programs))
        <x-filament::section>
            <div class="text-center py-8 text-gray-500">
                <p class="font-semibold text-lg">{{ __('admin.external.programs.empty') }}</p>
                <p class="text-sm mt-1">{{ __('admin.external.programs.empty_desc') }}</p>
            </div>
        </x-filament::section>
    @else
        {{-- Table --}}
        <x-filament::section>
            <div class="fi-ta">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5 text-start">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">ID</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.image') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.name') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">{{ __('admin.external.programs.category') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-end">{{ __('admin.external.programs.price') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">{{ __('admin.external.programs.duration') }}</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">{{ __('admin.external.programs.calories') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($programs as $idx => $program)
                                @php $progName = $resolveName($program['name'] ?? ''); $catDisplayName = $resolveName($program['category']['name'] ?? ''); @endphp
                                <tr wire:click="selectProgram({{ $idx }})" class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 font-mono">{{ $program['id'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(!empty($program['image_url']))
                                            <img src="{{ $program['image_url'] }}" alt="" class="w-8 h-8 rounded object-cover" />
                                        @else
                                            <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-800"></div>
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $progName }}</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(!empty($catDisplayName) && $catDisplayName !== '—')
                                            <x-filament::badge color="primary" size="sm">{{ $catDisplayName }}</x-filament::badge>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-gray-950 dark:text-white">{{ number_format($program['price']) }} SAR</td>
                                    <td class="fi-ta-cell px-3 py-3 text-center"><x-filament::badge color="info" size="sm">{{ $program['duration_days'] }}d</x-filament::badge></td>
                                    <td class="fi-ta-cell px-3 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                        @if($program['calories_min'] && $program['calories_max']){{ $program['calories_min'] }}-{{ $program['calories_max'] }}@elseif($program['calories_per_day']){{ $program['calories_per_day'] }}@else — @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="fi-ta-footer px-3 py-2 text-sm text-gray-500 border-t border-gray-200 dark:border-white/5">
                    {{ __('admin.external.total') }}: <strong>{{ count($programs) }}</strong>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Detail Infolist --}}
    @if($this->selectedProgram)
        @php
            $p = $this->selectedProgram;
            $pName = $resolveName($p['name'] ?? '');
            $pCat = $resolveName($p['category']['name'] ?? '');
        @endphp
        <x-filament::section icon="heroicon-o-information-circle" :heading="$pName" :description="'ID: ' . $p['id']" collapsible>
            <div class="flex justify-end mb-2">
                <x-filament::button color="gray" size="sm" wire:click="closeDetail">{{ __('Close') }}</x-filament::button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Left: Image + Basic --}}
                <div class="space-y-4">
                    @if(!empty($p['image_url']))
                        <img src="{{ $p['image_url'] }}" alt="" class="w-full max-w-xs rounded-lg object-cover" />
                    @endif

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">{{ __('admin.external.programs.category') }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $pCat }}</dd>

                        <dt class="text-gray-500">{{ __('admin.external.programs.price') }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ number_format($p['price']) }} SAR</dd>

                        @if($p['offer_price'] && $p['offer_price'] != $p['price'])
                            <dt class="text-gray-500">{{ __('admin.external.programs.offer_price') }}</dt>
                            <dd class="font-medium text-danger-600">{{ number_format($p['offer_price']) }} SAR</dd>
                        @endif

                        @if(!empty($p['weekly_price']))
                            <dt class="text-gray-500">{{ __('admin.external.programs.weekly') }}</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ number_format($p['weekly_price']) }} SAR</dd>
                        @endif
                    </dl>
                </div>

                {{-- Right: Metrics --}}
                <div class="space-y-4">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">{{ __('admin.external.programs.duration') }}</dt>
                        <dd><x-filament::badge color="info">{{ $p['duration_days'] }} {{ __('admin.external.programs.days') }}</x-filament::badge></dd>

                        <dt class="text-gray-500">{{ __('admin.external.programs.calories') }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">
                            @if($p['calories_min'] && $p['calories_max']){{ $p['calories_min'] }} - {{ $p['calories_max'] }} kcal
                            @elseif($p['calories_per_day']){{ $p['calories_per_day'] }} kcal
                            @else — @endif
                        </dd>

                        @if(!empty($p['description']))
                            <dt class="text-gray-500 col-span-2">{{ __('admin.external.programs.description') }}</dt>
                            <dd class="col-span-2 text-gray-700 dark:text-gray-300">{{ $p['description'] }}</dd>
                        @endif
                    </dl>

                    @if(!empty($p['badges']))
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('admin.external.programs.badges') }}</dt>
                            <div class="flex flex-wrap gap-1">
                                @foreach($p['badges'] as $badge)
                                    <x-filament::badge color="warning" size="sm">{{ $badge['name'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($p['calorie_options']))
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">{{ __('admin.external.programs.calories') }} Options</dt>
                            <div class="flex flex-wrap gap-1">
                                @foreach($p['calorie_options'] as $opt)
                                    <x-filament::badge color="gray" size="sm">{{ $opt['label'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
