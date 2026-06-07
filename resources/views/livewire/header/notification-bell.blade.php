@if($visible)
    <div class="header__action-chip header__action-chip--notif" style="--ha-i:0.5">
        <a
            href="{{ route('account.notifications.index') }}"
            class="header__notif-btn"
            aria-label="{{ __('account.notifications') }}"
            title="{{ __('account.notifications') }}"
        >
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            @if($unreadCount > 0)
                <span class="header__notif-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </a>
    </div>
@endif
