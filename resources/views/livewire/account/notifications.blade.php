<div class="space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('account.notifications') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('account.notifications_hint') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <span class="acc-chip acc-chip--warn">{{ $unreadCount }} {{ __('account.unread') }}</span>
                <button type="button" class="acc-btn acc-btn--ghost acc-btn--sm" wire:click="markAllRead" wire:loading.attr="disabled">
                    {{ __('account.mark_all_read') }}
                </button>
            @endif
            <button type="button" class="acc-btn acc-btn--muted acc-btn--sm" wire:click="refresh" wire:loading.attr="disabled">
                {{ __('account.refresh') }}
            </button>
        </div>
    </div>

    @if($notice)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>
    @endif
    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>
    @endif

    <div class="acc-card">
        @if($loading && empty($items))
            <div class="acc-empty">{{ __('account.loading') }}</div>
        @elseif(empty($items))
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                </div>
                <p>{{ __('account.no_notifications') }}</p>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($items as $item)
                    @php
                        $title = $item['title'] ?? $item['subject'] ?? '';
                        if (is_array($title)) {
                            $title = $title[app()->getLocale()] ?? $title['en'] ?? '';
                        }
                        $body = $item['body'] ?? $item['message'] ?? $item['content'] ?? '';
                        if (is_array($body)) {
                            $body = $body[app()->getLocale()] ?? $body['en'] ?? '';
                        }
                        $when = $item['created_at'] ?? $item['date'] ?? $item['sent_at'] ?? '';
                        $isUnread = true;
                        if (array_key_exists('is_read', $item)) {
                            $isUnread = ! filter_var($item['is_read'], FILTER_VALIDATE_BOOLEAN);
                        } elseif (! empty($item['read_at'])) {
                            $isUnread = false;
                        } elseif (array_key_exists('read', $item)) {
                            $isUnread = ! filter_var($item['read'], FILTER_VALIDATE_BOOLEAN);
                        }
                        $link = $item['url'] ?? $item['action_url'] ?? $item['deep_link'] ?? null;
                    @endphp
                    <li class="px-5 py-4 {{ $isUnread ? 'bg-sky-50/70 border-s-4 border-[#279ff9]' : 'opacity-75 hover:bg-gray-50' }} transition">
                        <div class="flex items-start gap-3">
                            @if($isUnread)
                                <span class="mt-1.5 w-2 h-2 rounded-full bg-[#279ff9] shrink-0" aria-hidden="true"></span>
                            @else
                                <span class="mt-1.5 w-2 h-2 shrink-0" aria-hidden="true"></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                @if($title)
                                    <p class="font-semibold text-gray-900">{{ $title }}</p>
                                @endif
                                @if($body)
                                    <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{{ $body }}</p>
                                @endif
                                @if($when)
                                    <p class="text-xs text-gray-400 mt-1.5">{{ $when }}</p>
                                @endif
                                @if($link && is_string($link))
                                    <a href="{{ $link }}" class="text-xs font-semibold text-blue-600 mt-2 inline-block">{{ __('account.view_details') }}</a>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($hasMore)
                <div class="p-4 border-t border-gray-100 text-center">
                    <button type="button" class="acc-btn acc-btn--ghost acc-btn--sm" wire:click="loadMore" wire:loading.attr="disabled">
                        {{ __('account.load_more') }}
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
