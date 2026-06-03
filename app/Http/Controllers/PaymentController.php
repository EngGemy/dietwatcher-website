<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Livewire\Cart\CartManager;
use App\Models\Payment;
use App\Services\AccountApiService;
use App\Services\Payment\MoyasarPaymentService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private MoyasarPaymentService $paymentService,
        private AccountApiService $accountApiService
    ) {}

    /**
     * Show the Moyasar payment form for a given payment session.
     */
    public function form(Request $request): View|RedirectResponse
    {
        $payment = Payment::where('order_number', $request->query('order'))
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::FAILED])
            ->first();

        if (! $payment) {
            return redirect()->route('checkout.index')
                ->with('error', __('payment.session_expired'));
        }

        // Reset failed payments so they can be retried
        if ($payment->status === PaymentStatus::FAILED || $payment->isExpired()) {
            $payment->update([
                'status' => PaymentStatus::PENDING,
                'expires_at' => now()->addMinutes(30),
            ]);
        }

        return view('pages.payment', [
            'payment' => $payment,
            'publishableKey' => $this->paymentService->getPublishableKey(),
            'callbackUrl' => route('payment.callback'),
            'amountInHalalas' => $payment->amount,
            'amountDisplay' => number_format($payment->amount_in_sar, 2),
            'currency' => $payment->currency,
            'description' => __('payment.description', [
                'order' => $payment->order_number,
            ]),
        ]);
    }

    /**
     * Handle callback redirect from Moyasar after payment (3D Secure redirect).
     */
    public function callback(Request $request): RedirectResponse
    {
        $moyasarId = $request->query('id');
        $status = $request->query('status');
        $message = $request->query('message');
        $orderNumber = $request->query('order');
        $subscriptionId = (int) $request->query('subscription', 0);

        if ($subscriptionId <= 0) {
            $checkoutSession = session('checkout_api_subscription_checkout');
            if (is_array($checkoutSession)) {
                $subscriptionId = (int) ($checkoutSession['subscription_id'] ?? 0);
            }
        }

        if (($orderNumber === null || $orderNumber === '') && $subscriptionId > 0) {
            return $this->handleSubscriptionPaymentCallback($request, $subscriptionId, $moyasarId, $status, $message);
        }

        Log::info('Moyasar callback received', [
            'moyasar_id' => $moyasarId,
            'status' => $status,
            'order' => $orderNumber,
        ]);

        // Find payment by order number
        $payment = Payment::where('order_number', $orderNumber)->first();

        if (! $payment) {
            Log::error('Payment not found for callback', ['order' => $orderNumber]);

            return redirect()->route('checkout.index')
                ->with('error', __('payment.errors.generic'));
        }

        // If Moyasar says failed, handle it directly
        if ($status !== 'paid') {
            $userMessage = $this->paymentService->parseErrorMessage($message);

            Log::warning('Payment failed from callback', [
                'order' => $orderNumber,
                'moyasar_id' => $moyasarId,
                'status' => $status,
                'message' => $message,
            ]);

            $payment->update([
                'moyasar_id' => $moyasarId,
                'status' => PaymentStatus::FAILED,
                'message' => $message,
            ]);

            return redirect()->route('payment.result', ['order' => $payment->order_number])
                ->with('payment_error', $userMessage);
        }

        // Verify payment with Moyasar API
        $response = $this->paymentService->verify($moyasarId);

        if (! $response) {
            Log::error('Could not verify payment with Moyasar', [
                'moyasar_id' => $moyasarId,
            ]);

            $payment->update([
                'moyasar_id' => $moyasarId,
                'status' => PaymentStatus::FAILED,
                'message' => 'Verification failed',
            ]);

            return redirect()->route('payment.result', ['order' => $payment->order_number])
                ->with('payment_error', __('payment.errors.verification_failed'));
        }

        // Update payment with verified response
        $payment = $this->paymentService->updatePaymentFromResponse($payment, $response);

        Log::info('Payment verified', [
            'order' => $payment->order_number,
            'status' => $payment->status->value,
            'moyasar_id' => $payment->moyasar_id,
        ]);

        // Clear cart and send confirmation SMS on successful payment
        if ($payment->status === PaymentStatus::PAID) {
            $externalToken = (string) session('external_api_token', '');
            if ($externalToken !== '') {
                if ($payment->kind === PaymentKind::Subscription) {
                    // Subscription was created via API bootstrap; webhook confirms payment — no second create.
                    $payment->update([
                        'external_sync_status' => 'skipped',
                        'external_sync_message' => 'api_bootstrap_subscription',
                        'external_subscription_id' => $payment->external_subscription_id,
                    ]);
                } else {
                    $syncResult = $this->accountApiService->syncPaidPaymentToExternalOrder($payment, $externalToken);
                    $payment->update([
                        'external_sync_status' => $syncResult['ok'] ? 'synced' : 'failed',
                        'external_sync_message' => $syncResult['ok'] ? null : (string) ($syncResult['message'] ?? 'sync_failed'),
                        'external_order_id' => $syncResult['external_order_id'] ?? null,
                        'external_order_number' => $syncResult['external_order_number'] ?? null,
                        'external_synced_at' => $syncResult['ok'] ? now() : null,
                    ]);
                    $this->accountApiService->clearOrdersCache($externalToken);
                }
            } else {
                $payment->update([
                    'external_sync_status' => 'skipped',
                    'external_sync_message' => 'missing_external_token',
                ]);
            }

            session()->forget('checkout_moyasar_order');
            session()->forget(CartManager::SESSION_MARKET);
            session()->forget(CartManager::SESSION_SUBSCRIPTION);

            try {
                $smsMessage = __('sms.order_confirmed', [
                    'order' => $payment->order_number,
                    'amount' => number_format($payment->amount_in_sar, 2),
                ]);
                SmsService::create()->send($payment->customer_phone, $smsMessage);
            } catch (\Exception $e) {
                Log::warning('Failed to send order confirmation SMS', [
                    'order' => $payment->order_number,
                    'phone' => $payment->customer_phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('payment.result', ['order' => $payment->order_number]);
    }

    /**
     * Subscription checkout callback — redirect to API polling page (no local Payment record).
     */
    protected function handleSubscriptionPaymentCallback(
        Request $request,
        int $subscriptionId,
        ?string $moyasarId,
        ?string $status,
        ?string $message,
    ): RedirectResponse {
        Log::info('Moyasar subscription callback received', [
            'subscription_id' => $subscriptionId,
            'moyasar_id' => $moyasarId,
            'status' => $status,
        ]);

        if ($status !== 'paid') {
            $userMessage = $this->paymentService->parseErrorMessage($message);

            return redirect()->route('payment.subscription-confirm', [
                'subscription' => $subscriptionId,
                'failed' => 1,
            ])->with('payment_error', $userMessage);
        }

        $checkoutSession = session('checkout_api_subscription_checkout');
        $externalPaymentId = is_array($checkoutSession)
            ? (int) data_get($checkoutSession, 'bootstrap.external_payment_id', 0)
            : 0;

        $forwardQuery = array_filter(array_merge(
            $request->query(),
            [
                'id' => $moyasarId,
                'status' => $status,
                'message' => $message,
                'subscription_id' => $subscriptionId > 0 ? (string) $subscriptionId : null,
                'payment_id' => $externalPaymentId > 0 ? (string) $externalPaymentId : null,
            ],
        ), static fn ($value) => $value !== null && $value !== '');

        $apiForward = $this->accountApiService->notifySubscriptionMoyasarPayment($forwardQuery);
        Log::info('Forwarded Moyasar subscription callback to external API', [
            'subscription_id' => $subscriptionId,
            'moyasar_id' => $moyasarId,
            'external_payment_id' => $externalPaymentId > 0 ? $externalPaymentId : null,
            'api_ok' => $apiForward['ok'] ?? false,
            'api_message' => $apiForward['message'] ?? '',
        ]);

        session([
            'pending_subscription_confirm' => [
                'subscription_id' => $subscriptionId,
                'moyasar_id' => $moyasarId,
                'external_payment_id' => $externalPaymentId > 0 ? $externalPaymentId : null,
                'status' => $status,
                'message' => $message,
                'last_forward_at' => time(),
                'last_forward_ok' => $this->accountApiService->paymentNotifyResponseSucceeded($apiForward),
            ],
        ]);
        session()->forget('checkout_api_subscription_checkout');
        session()->forget(CartManager::SESSION_SUBSCRIPTION);
        session()->forget(CartManager::SESSION_MARKET);

        return redirect()->route('payment.subscription-confirm', array_filter([
            'subscription' => $subscriptionId,
            'moyasar_id' => $moyasarId,
            'status' => $status,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * Poll external API until subscription is active/paid.
     */
    public function subscriptionConfirm(Request $request): View
    {
        $subscriptionId = (int) $request->query('subscription', 0);
        $failed = $request->boolean('failed');

        return view('pages.payment-subscription-confirm', [
            'subscriptionId' => $subscriptionId,
            'failed' => $failed,
            'errorMessage' => session('payment_error'),
        ]);
    }

    /**
     * Show payment result page (success or failure).
     */
    public function result(Request $request): View|RedirectResponse
    {
        $payment = Payment::where('order_number', $request->query('order'))->first();

        if (! $payment) {
            return redirect()->route('home');
        }

        return view('pages.payment-result-premium', [
            'payment' => $payment,
            'success' => $payment->status === PaymentStatus::PAID,
            'errorMessage' => session('payment_error'),
            'autoDownloadInvoice' => $request->boolean('auto_download', true),
        ]);
    }

    /**
     * Download a lightweight HTML invoice from the confirmation page.
     */
    public function downloadInvoice(Request $request): Response|RedirectResponse
    {
        $payment = Payment::where('order_number', $request->query('order'))->first();
        if (! $payment) {
            return redirect()->route('home');
        }

        if ($payment->status !== PaymentStatus::PAID) {
            return redirect()->route('payment.result', ['order' => $payment->order_number]);
        }

        $html = view('pages.payment-invoice-download', [
            'payment' => $payment,
        ])->render();

        $file = 'invoice-'.strtolower($payment->order_number).'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$file.'"',
        ]);
    }
}
