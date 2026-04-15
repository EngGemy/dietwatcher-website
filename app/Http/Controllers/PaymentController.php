<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Livewire\Cart\CartManager;
use App\Models\Payment;
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
        private MoyasarPaymentService $paymentService
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
            session()->forget('checkout_moyasar_order');
            session()->forget(CartManager::SESSION_MARKET);
            session()->forget(CartManager::SESSION_SUBSCRIPTION);

            try {
                $message = __('sms.order_confirmed', [
                    'order' => $payment->order_number,
                    'amount' => number_format($payment->amount_in_sar, 2),
                ]);
                SmsService::create()->send($payment->customer_phone, $message);
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

        $file = 'invoice-' . strtolower($payment->order_number) . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $file . '"',
        ]);
    }
}
