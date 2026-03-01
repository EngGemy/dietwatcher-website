<x-filament-widgets::widget>
    <x-filament::section heading="Latest Orders (API)" icon="heroicon-o-shopping-cart">
        @php $orders = $this->getOrders(); @endphp

        @if(empty($orders))
            <div class="text-center py-6 text-gray-500">
                <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                <p class="font-medium">No orders available</p>
                <p class="text-sm">Orders from the external API will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-start py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">#</th>
                            <th class="text-start py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Customer</th>
                            <th class="text-start py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Status</th>
                            <th class="text-end py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Total</th>
                            <th class="text-start py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-2.5 px-3 font-medium">{{ $order['id'] ?? '-' }}</td>
                                <td class="py-2.5 px-3">{{ $order['customer']['name'] ?? $order['customer_name'] ?? '-' }}</td>
                                <td class="py-2.5 px-3">
                                    @php
                                        $status = $order['status'] ?? 'unknown';
                                        $color = match($status) {
                                            'completed', 'delivered' => 'success',
                                            'pending', 'processing' => 'warning',
                                            'cancelled', 'failed' => 'danger',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-filament::badge :color="$color">
                                        {{ ucfirst($status) }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-2.5 px-3 text-end font-semibold">
                                    SAR {{ number_format((float)($order['total'] ?? $order['total_amount'] ?? 0), 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-gray-500">
                                    {{ isset($order['created_at']) ? \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
