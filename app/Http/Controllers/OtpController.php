<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApiAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    public function __construct(
        private ApiAuthService $auth,
    ) {}

    /**
     * Send OTP to the given phone via the external API
     * (POST /login/ordinary/reset).
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        // Lightweight session-side rate limit (1 send per 60s per phone).
        $sessionKey = 'otp_sent_at_' . md5($phone);
        $lastSentAt = session($sessionKey);

        if ($lastSentAt && (time() - $lastSentAt) < 60) {
            $remaining = 60 - (time() - $lastSentAt);
            return response()->json([
                'success' => false,
                'message' => __('otp.wait_seconds', ['seconds' => $remaining]),
            ], 429);
        }

        $apiResponse = $this->auth->sendOtp($phone);

        // The external API typically returns 2xx with a `success`/`status` flag.
        // Treat the request as successful unless it explicitly says otherwise.
        $ok = ($apiResponse['success'] ?? $apiResponse['status'] ?? true) !== false;

        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => $apiResponse['message'] ?? __('otp.send_failed'),
            ], 422);
        }

        session([
            'otp_phone'  => $phone,
            $sessionKey  => time(),
        ]);

        Log::info('OTP requested via external API', ['phone' => $phone]);

        $payload = [
            'success' => true,
            'message' => $apiResponse['message'] ?? __('otp.code_sent'),
        ];

        // Surface the test code in non-production for easier QA.
        if (! app()->isProduction() && ! empty($apiResponse['code'])) {
            $payload['otp'] = (string) $apiResponse['code'];
        }

        return response()->json($payload);
    }

    /**
     * Verify the OTP via the external API
     * (POST /login/ordinary/verify).
     *
     * - If the customer already exists, the API returns a token → store it
     *   in the session and the user is logged in.
     * - If the phone is new (`is_continue = true`), tell the frontend that
     *   a simple registration step is required.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'otp'   => 'required|string|size:4',
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $apiResponse = $this->auth->verifyOtp($phone, $validated['otp']);

        // Normalize the response shape — different env builds nest data differently.
        $data        = $apiResponse['data'] ?? $apiResponse;
        $isContinue  = (bool) ($data['is_continue'] ?? false);
        $token       = (string) ($data['token'] ?? '');
        $errorMsg    = $apiResponse['message'] ?? null;

        // Existing customer → token is ready, store and finish.
        if (! $isContinue && $token !== '') {
            $this->loginCustomer($phone, $token, $data['profile'] ?? null);

            return response()->json([
                'success' => true,
                'message' => __('otp.verified'),
            ]);
        }

        // New phone → frontend must collect name/email and register.
        if ($isContinue) {
            session([
                'phone_verified' => $phone,
            ]);

            return response()->json([
                'success'             => true,
                'needs_registration'  => true,
                'phone'               => $phone,
                'message'             => __('otp.verified'),
            ]);
        }

        // Anything else is an OTP failure.
        return response()->json([
            'success' => false,
            'message' => $errorMsg ?: __('otp.invalid_code'),
        ], 422);
    }

    /**
     * Complete a simple registration for a brand-new phone after OTP verify
     * (POST /register/simple-register). Stores the returned token in the
     * session so the rest of the checkout can act on behalf of the customer.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
        ]);

        $phone = (string) session('phone_verified', '');

        if ($phone === '') {
            return response()->json([
                'success' => false,
                'message' => __('otp.not_found'),
            ], 422);
        }

        $apiResponse = $this->auth->simpleRegister(array_filter([
            'name'   => $validated['name'],
            'mobile' => $phone,
            'email'  => $validated['email']  ?? null,
            'gender' => $validated['gender'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        $data  = $apiResponse['data'] ?? $apiResponse;
        $token = (string) ($data['token'] ?? '');

        if ($token === '') {
            Log::warning('Simple register returned no token', ['response' => $apiResponse]);

            return response()->json([
                'success' => false,
                'message' => $apiResponse['message'] ?? __('auth.register_failed'),
            ], 422);
        }

        $this->loginCustomer($phone, $token, $data['profile'] ?? null);

        return response()->json([
            'success' => true,
            'message' => $apiResponse['message'] ?? __('otp.verified'),
        ]);
    }

    /**
     * Sign the customer out: revoke the API token, clear local session,
     * then bounce back to wherever they came from.
     */
    public function logout(Request $request): RedirectResponse
    {
        $token = (string) session('customer_token', '');

        if ($token !== '') {
            $this->auth->logout($token);
        }

        session()->forget([
            'customer_token',
            'customer_phone',
            'customer_profile',
            'phone_verified',
        ]);

        return back()->with('status', __('You have been signed out.'));
    }

    /**
     * Persist the customer session after a successful login or registration.
     */
    private function loginCustomer(string $phone, string $token, mixed $profile = null): void
    {
        session([
            'customer_token'   => $token,
            'customer_phone'   => $phone,
            'customer_profile' => $profile,
            'phone_verified'   => $phone,
        ]);
        session()->forget(['otp_phone']);
    }

    /**
     * Normalize a Saudi phone number to E.164 (`+966XXXXXXXXX`).
     * The external API expects this format.
     */
    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[\s\-\(\)]+/', '', $phone) ?? '';

        if (str_starts_with($clean, '+')) {
            return $clean;
        }
        if (str_starts_with($clean, '00')) {
            return '+' . substr($clean, 2);
        }
        if (str_starts_with($clean, '966')) {
            return '+' . $clean;
        }

        $local = ltrim($clean, '0');
        return '+966' . $local;
    }
}
