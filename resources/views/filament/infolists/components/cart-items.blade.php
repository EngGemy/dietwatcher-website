@php
    $items = $getRecord()->cart_items ?? [];
@endphp

@if(empty($items))
    <div class="text-center py-4 text-gray-500">
        <p>{{ __('admin.payments.no_items') }}</p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-start py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('admin.payments.fields.item_image') }}</th>
                    <th class="text-start py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('admin.payments.fields.item_name') }}</th>
                    <th class="text-center py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('admin.payments.fields.item_qty') }}</th>
                    <th class="text-end py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('admin.payments.fields.item_price') }}</th>
                    <th class="text-end py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('admin.payments.fields.item_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    @php
                        $name = $item['name'] ?? '—';
                        $image = $item['image'] ?? '';
                        $price = (float) ($item['price'] ?? 0);
                        $qty = (int) ($item['quantity'] ?? 1);
                        $mealType = $item['options']['mealType'] ?? '';
                        $calories = $item['options']['calories'] ?? '';
                        $lineTotal = $price * $qty;
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-3">
                            @if($image)
                                <img src="{{ $image }}" alt="" class="w-10 h-10 rounded object-cover" />
                            @else
                                <div class="w-10 h-10 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <x-heroicon-o-photo class="w-4 h-4 text-gray-400" />
                                </div>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $name }}</div>
                            @if($mealType || $calories)
                                <div class="text-xs text-gray-500 mt-0.5">
                                    @if($mealType){{ ucfirst($mealType) }}@endif
                                    @if($mealType && $calories) · @endif
                                    @if($calories){{ $calories }} kcal @endif
                                </div>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-center">{{ $qty }}</td>
                        <td class="py-2 px-3 text-end">{{ number_format($price, 2) }} SAR</td>
                        <td class="py-2 px-3 text-end font-semibold">{{ number_format($lineTotal, 2) }} SAR</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
