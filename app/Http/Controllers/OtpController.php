<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApiAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct(
        protected ApiAuthService $apiAuth
    ) {}

    /**
     * Use external API OTP (login/ordinary/reset) when enabled and EXTERNAL_API_URL is set.
     */
    private function useExternalCheckoutLogin(): bool
    {
        return (bool) config('services.external_api.checkout_use_external_login', false)
            && filled(config('services.external_api.url'));
    }

    /**
     * Whether external API is available (for profile/address hydration after local OTP).
     */
    private function hasExternalApi(): bool
    {
        return filled(config('services.external_api.url'));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{token: string, is_continue: bool, profile: array<string, mixed>}
     */
    private function parseExternalVerifyBody(array $body): array
    {
        $d = isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
        $token = (string) ($d['token'] ?? $d['access_token'] ?? $body['token'] ?? '');
        $isContinue = filter_var($d['is_continue'] ?? $body['is_continue'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $profile = $d['profile'] ?? $body['profile'] ?? [];
        if (! is_array($profile)) {
            $profile = [];
        }

        return ['token' => $token, 'is_continue' => $isContinue, 'profile' => $profile];
    }

    /**
     * After local OTP verification, try to get external API token + profile + addresses
     * by calling simple-register (creates account if new, or returns existing).
     */
    private function hydrateFromExternalApi(string $phone, ?string $deviceId = null): array
    {
        $result = [
            'token' => '',
            'profile' => [],
            'addresses' => [],
            'is_continue' => null,
        ];

        if (! $this->hasExternalApi()) {
            return $result;
        }

        try {
            // First try: send + verify OTP on external API to get a real token
            // This handles existing users who already have accounts
            $sendResult = $this->apiAuth->sendOtp($phone);

            if ($sendResult['_http_ok'] ?? false) {
                // In non-production, external API may return the OTP code directly
                $externalOtp = $sendResult['otp'] ?? null;

                if ($externalOtp) {
                    $deviceId ??= 'web-checkout-' . Str::uuid()->toString();
                    $verifyResult = $this->apiAuth->verifyOtp($phone, (string) $externalOtp, $deviceId);

                    if ($verifyResult['_http_ok'] ?? false) {
                        $parsed = $this->parseExternalVerifyBody($verifyResult);

                        if ($parsed['token'] !== '') {
                            $result['token'] = $parsed['token'];
                            $result['profile'] = $parsed['profile'];
                            $result['is_continue'] = $parsed['is_continue'];

                            $addresses = $this->apiAuth->getAddresses($parsed['token']);
                            $result['addresses'] = is_array($addresses) ? $addresses : [];

                            return $result;
                        }
                    }
                }
            }

            // IMPORTANT:
            // Do NOT silently auto-register users here. Registration must be
            // an explicit frontend step.
        } catch (\Exception $e) {
            Log::warning('OtpController: external API hydration failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    private function markPendingRegistrationMobile(string $mobile): void
    {
        $mobile = preg_replace('/\s+/', '', (string) $mobile) ?? $mobile;
        session([
            'pending_register_mobile' => $mobile,
            'pending_register_expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Send OTP code via SMS.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = preg_replace('/\s+/', '', (string) $validated['phone']) ?? (string) $validated['phone'];

        $sessionKey = 'otp_sent_at_'.md5($phone);
        $lastSentAt = session($sessionKey);

        if ($lastSentAt && (time() - $lastSentAt) < 60) {
            $remaining = 60 - (time() - $lastSentAt);

            return response()->json([
                'success' => false,
                'message' => __('otp.wait_seconds', ['seconds' => $remaining]),
            ], 429);
        }

        if ($this->useExternalCheckoutLogin()) {
            $remote = $this->apiAuth->sendOtp($phone);

            if (! ($remote['_http_ok'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $remote['message'] ?? __('otp.send_failed'),
                ], 422);
            }

            session([
                'checkout_otp_external' => true,
                'otp_phone' => $phone,
                'otp_expires_at' => now()->addMinutes(5),
                $sessionKey => time(),
            ]);

            $response = [
                'success' => true,
                'message' => $remote['message'] ?? __('otp.code_sent'),
                'external' => true,
            ];

            if (! app()->isProduction() && isset($remote['otp'])) {
                $response['otp'] = $remote['otp'];
            }

            return response()->json($response);
        }

        // Local OTP: generate code and send via SMS provider (mshastra/connect)
        $otp = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        session([
            'otp_code' => $otp,
            'otp_phone' => $phone,
            'otp_expires_at' => now()->addMinutes(5),
            'checkout_otp_external' => false,
            $sessionKey => time(),
        ]);

        $message = __('sms.otp_code', ['code' => $otp]);

        try {
            \App\Services\SmsService::create()->send($phone, $message);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP SMS', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('otp.send_failed'),
            ]);
        }

        Log::info('OTP sent', ['phone' => $phone]);

        $response = [
            'success' => true,
            'message' => __('otp.code_sent'),
            'external' => false,
        ];

        if (! app()->isProduction()) {
            $response['otp'] = $otp;
        }

        return response()->json($response);
    }

    /**
     * Verify OTP code (local session or external login/ordinary/verify).
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'otp' => 'required|string|size:4',
            'device_id' => 'nullable|string|max:255',
        ]);

        $validated['phone'] = preg_replace('/\s+/', '', (string) $validated['phone']) ?? (string) $validated['phone'];

        $storedPhone = session('otp_phone');
        $expiresAt = session('otp_expires_at');

        if (! $storedPhone || ! $expiresAt) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'otp_not_found',
                'message' => __('otp.not_found'),
                'errors' => [],
            ]);
        }

        if (now()->isAfter($expiresAt)) {
            session()->forget(['otp_code', 'otp_phone', 'otp_expires_at', 'checkout_otp_external']);

            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'otp_expired',
                'message' => __('otp.expired'),
                'errors' => [],
            ]);
        }

        $storedPhoneNormalized = preg_replace('/\s+/', '', (string) $storedPhone) ?? (string) $storedPhone;
        if ($storedPhoneNormalized !== $validated['phone']) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'phone_mismatch',
                'message' => __('otp.phone_mismatch'),
                'errors' => [],
            ]);
        }

        $external = (bool) session('checkout_otp_external', false);

        // ─── Path A: Fully external OTP (external API sent the code) ───
        if ($external) {
            $deviceId = $validated['device_id'] ?? null;
            if ($deviceId === null || $deviceId === '') {
                $deviceId = 'web-checkout-'.Str::uuid()->toString();
            }

            $remote = $this->apiAuth->verifyOtp($validated['phone'], $validated['otp'], $deviceId);

            if (! ($remote['_http_ok'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'invalid_otp',
                    'message' => $remote['message'] ?? __('otp.invalid_code'),
                    'errors' => [],
                ], 422);
            }

            $parsed = $this->parseExternalVerifyBody($remote);

            // New user path: OTP is valid but account creation still required.
            if ($parsed['is_continue'] === true) {
                $this->markPendingRegistrationMobile($validated['phone']);
                session()->forget(['otp_code', 'otp_phone', 'otp_expires_at', 'checkout_otp_external']);

                return response()->json([
                    'ok' => true,
                    'success' => true,
                    'is_continue' => true,
                    'needs_registration' => true,
                    'profile' => $parsed['profile'],
                    'message' => __('otp.verified'),
                ]);
            }

            if ($parsed['token'] === '') {
                Log::warning('External OTP verify returned empty token', ['body_keys' => array_keys($remote)]);

                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'external_api_error',
                    'message' => $remote['message'] ?? __('otp.invalid_code'),
                    'errors' => [],
                ], 422);
            }

            session([
                'phone_verified' => $validated['phone'],
                'external_api_token' => $parsed['token'],
                'external_api_profile' => $parsed['profile'],
                'external_login_is_continue' => $parsed['is_continue'],
            ]);
            session()->forget([
                'otp_code',
                'otp_phone',
                'otp_expires_at',
                'checkout_otp_external',
                'pending_register_mobile',
                'pending_register_expires_at',
            ]);

            $addresses = $this->apiAuth->getAddresses($parsed['token']);
            if (! is_array($addresses)) {
                $addresses = [];
            }

            return response()->json([
                'ok' => true,
                'success' => true,
                'message' => __('otp.verified'),
                'addresses' => $addresses,
                'profile' => $parsed['profile'],
                'is_continue' => $parsed['is_continue'],
                'needs_registration' => false,
            ]);
        }

        // ─── Path B: Local OTP (we sent the code via SMS) ──────────────
        $storedOtp = session('otp_code');
        if (! $storedOtp) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'otp_not_found',
                'message' => __('otp.not_found'),
                'errors' => [],
            ]);
        }

        if ($storedOtp !== $validated['otp']) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'invalid_otp',
                'message' => __('otp.invalid_code'),
                'errors' => [],
            ]);
        }

        // Local OTP verified — now hydrate profile/addresses from external API
        $deviceId = $validated['device_id'] ?? null;
        if ($deviceId === null || $deviceId === '') {
            $deviceId = 'web-checkout-' . Str::uuid()->toString();
        }

        $externalData = $this->hydrateFromExternalApi($validated['phone'], $deviceId);
        $isContinue = filter_var($externalData['is_continue'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        // Existing user with token => full authentication session.
        if (($externalData['token'] ?? '') !== '' && $isContinue !== true) {
            session([
                'phone_verified' => $validated['phone'],
                'external_api_token' => $externalData['token'],
                'external_api_profile' => $externalData['profile'],
                'external_login_is_continue' => false,
            ]);
            session()->forget([
                'pending_register_mobile',
                'pending_register_expires_at',
            ]);

            session()->forget([
                'otp_code',
                'otp_phone',
                'otp_expires_at',
                'checkout_otp_external',
            ]);

            return response()->json([
                'ok' => true,
                'success' => true,
                'message' => __('otp.verified'),
                'addresses' => $externalData['addresses'],
                'profile' => $externalData['profile'],
                'is_continue' => false,
                'needs_registration' => false,
            ]);
        }

        // New/unknown user path => require explicit register step.
        $this->markPendingRegistrationMobile($validated['phone']);
        session()->forget([
            'external_api_token',
            'external_api_profile',
            'external_login_is_continue',
            'phone_verified',
        ]);
        session()->forget([
            'otp_code',
            'otp_phone',
            'otp_expires_at',
            'checkout_otp_external',
        ]);

        return response()->json([
            'ok' => true,
            'success' => true,
            'is_continue' => true,
            'needs_registration' => true,
            'profile' => $externalData['profile'],
            'message' => __('otp.verified'),
        ]);
    }

    /**
     * Register new checkout user after OTP verify indicated needs_registration=true.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255',
            'gender' => 'required|in:male,female',
            'avatar' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'validation_error',
                'message' => __('validation.required'),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $pendingMobile = (string) session('pending_register_mobile', '');
        $pendingMobile = preg_replace('/\s+/', '', $pendingMobile) ?? $pendingMobile;
        $pendingExpiresAt = session('pending_register_expires_at');

        if ($pendingMobile === '' || ! $pendingExpiresAt) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'validation_error',
                'message' => __('otp.not_found'),
                'errors' => ['mobile' => [__('otp.not_found')]],
            ], 422);
        }

        try {
            $expiresAt = $pendingExpiresAt instanceof Carbon ? $pendingExpiresAt : Carbon::parse((string) $pendingExpiresAt);
        } catch (\Throwable $e) {
            $expiresAt = now()->subSecond();
        }

        if (now()->isAfter($expiresAt)) {
            session()->forget(['pending_register_mobile', 'pending_register_expires_at']);

            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'validation_error',
                'message' => __('otp.expired'),
                'errors' => ['mobile' => [__('otp.expired')]],
            ], 422);
        }

        $payload = [
            'name' => (string) $validator->validated()['name'],
            'email' => (string) $validator->validated()['email'],
            'gender' => (string) $validator->validated()['gender'],
            'mobile' => $pendingMobile,
            // Some API deployments accept "phone" instead of "mobile".
            'phone' => $pendingMobile,
        ];

        $register = $this->apiAuth->simpleRegister($payload);
        $registerOk = (bool) (
            $register['success']
            ?? $register['ok']
            ?? $register['status']
            ?? false
        );

        if (! $registerOk) {
            $errors = is_array($register['errors'] ?? null) ? $register['errors'] : [];
            $message = (string) ($register['message'] ?? __('auth.register_failed'));
            // Check structured errors first (most reliable)
            if (isset($errors['email'])) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'email_taken',
                    'message' => $message,
                    'errors' => $errors,
                ], 422);
            }

            if (isset($errors['mobile']) || isset($errors['phone'])) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'mobile_taken',
                    'message' => $message,
                    'errors' => $errors,
                ], 409);
            }

            // Fallback: check message text only as last resort
            $msgLower = Str::lower($message);
            if (str_contains($msgLower, 'email')) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'email_taken',
                    'message' => $message,
                    'errors' => $errors,
                ], 422);
            }

            if (str_contains($msgLower, 'mobile') || str_contains($msgLower, 'phone')) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'mobile_taken',
                    'message' => $message,
                    'errors' => $errors,
                ], 409);
            }

            if (! empty($errors)) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => $message,
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'server_error',
                'message' => $message,
                'errors' => [],
            ], 500);
        }

        // After successful registration, trigger a fresh OTP send for step 3.
        $remote = $this->apiAuth->sendOtp($pendingMobile);
        if (! ($remote['_http_ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'server_error',
                'message' => $remote['message'] ?? __('otp.send_failed'),
                'errors' => [],
            ], 500);
        }

        session([
            'checkout_otp_external' => true,
            'otp_phone' => $pendingMobile,
            'otp_expires_at' => now()->addMinutes(5),
            'otp_sent_at_'.md5($pendingMobile) => time(),
        ]);

        return response()->json([
            'ok' => true,
            'success' => true,
            'message' => $remote['message'] ?? __('otp.code_sent'),
        ]);
    }
}
