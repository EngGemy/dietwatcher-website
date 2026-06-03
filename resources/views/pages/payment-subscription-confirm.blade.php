@extends('layouts.app')

@section('title', ($failed ? __('payment.failed_title') : __('payment.confirming_title')) . ' | ' . $siteName)

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
    <div class="container max-w-[720px]">
        @if($failed)
            <div class="rounded-xl border border-gray-200 bg-white p-6 md:p-10 shadow-sm text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('payment.failed_heading') }}</h1>
                <p class="text-gray-600 mb-6">{{ $errorMessage ?? __('payment.failed_message') }}</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ route('checkout.index') }}" class="btn btn--primary btn--md">{{ __('payment.back_to_checkout') }}</a>
                    <a href="{{ route('home') }}" class="btn btn--outline btn--md">{{ __('payment.back_to_home') }}</a>
                </div>
            </div>
        @else
            <div id="subscription-confirm-root" class="rounded-xl border border-gray-200 bg-white p-6 md:p-10 shadow-sm text-center" data-subscription-id="{{ (int) $subscriptionId }}">
                <div id="confirm-spinner" class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-10 w-10 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
                <h1 id="confirm-title" class="text-2xl font-bold text-gray-900 mb-2">{{ __('payment.confirming_title') }}</h1>
                <p id="confirm-message" class="text-gray-600 mb-6">{{ __('payment.confirming_subtitle') }}</p>
                <div id="confirm-actions" class="hidden flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ route('account.subscriptions.index') }}" class="btn btn--primary btn--md">{{ __('account.go_to_dashboard') }}</a>
                    <a href="{{ route('home') }}" class="btn btn--outline btn--md">{{ __('payment.back_to_home') }}</a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
@if(! $failed && $subscriptionId > 0)
<script>
(function () {
    const root = document.getElementById('subscription-confirm-root');
    if (!root) return;

    const subscriptionId = parseInt(root.dataset.subscriptionId || '0', 10);
    if (!subscriptionId) return;

    const titleEl = document.getElementById('confirm-title');
    const messageEl = document.getElementById('confirm-message');
    const spinnerEl = document.getElementById('confirm-spinner');
    const actionsEl = document.getElementById('confirm-actions');

    const successTitle = @json(__('payment.confirmed_title'));
    const successMessage = @json(__('payment.subscription_confirmed_subtitle'));
    const pendingTitle = @json(__('payment.pending_title'));
    const pendingMessage = @json(__('payment.confirming_check_later'));
    const statusUrl = @json(route('checkout.subscription-status')) + '?' + new URLSearchParams((() => {
        const params = new URLSearchParams(window.location.search);
        const query = { id: String(subscriptionId) };
        const moyasarId = params.get('moyasar_id');
        const status = params.get('status');
        const paymentId = params.get('payment_id');
        if (moyasarId) query.moyasar_id = moyasarId;
        if (status) query.status = status;
        if (paymentId) query.payment_id = paymentId;
        return query;
    })()).toString();

    let attempt = 0;
    const maxAttempts = 18;
    const delays = [2000, 3000, 4000, 5000, 5000, 5000, 6000, 6000, 6000, 7000, 7000, 7000, 8000, 8000, 8000, 9000, 9000, 9000];

    function showSuccess() {
        if (spinnerEl) {
            spinnerEl.innerHTML = '<img src="{{ asset('assets/images/icons/check-success.svg') }}" class="h-16 w-16" alt="" />';
            spinnerEl.classList.remove('bg-blue-50');
            spinnerEl.classList.add('bg-green-50');
        }
        if (titleEl) titleEl.textContent = successTitle;
        if (messageEl) messageEl.textContent = successMessage;
        if (actionsEl) {
            actionsEl.classList.remove('hidden');
            actionsEl.classList.add('flex');
        }
    }

    function showPending() {
        if (spinnerEl) spinnerEl.classList.add('hidden');
        if (titleEl) titleEl.textContent = pendingTitle;
        if (messageEl) messageEl.textContent = pendingMessage;
        if (actionsEl) {
            actionsEl.classList.remove('hidden');
            actionsEl.classList.add('flex');
        }
    }

    async function poll() {
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success && data.confirmed) {
                showSuccess();
                return;
            }
        } catch (e) {}

        attempt += 1;
        if (attempt >= maxAttempts) {
            showPending();
            return;
        }
        setTimeout(poll, delays[Math.min(attempt, delays.length - 1)]);
    }

    poll();
})();
</script>
@endif
@endpush
