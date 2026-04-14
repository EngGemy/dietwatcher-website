<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApiAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'is_continue' => false,
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

            // Fallback: simple-register to create/find user and get token
            $registerResult = $this->apiAuth->simpleRegister([
                'name' => 'Customer',
                'mobile' => $phone,
                'email' => '',
                'gender' => 'male',
            ]);

            $registerData = $registerResult['data'] ?? $registerResult;
            $token = (string) ($registerData['token'] ?? $registerResult['token'] ?? '');

            if ($token !== '') {
                $profile = $registerData['profile'] ?? $registerResult['profile'] ?? [];
                $customer = $registerData['customer'] ?? [];

                // Build profile from customer data if profile is empty
                if (empty($profile) && ! empty($customer)) {
                    $profile = [
                        'id' => $customer['id'] ?? null,
                        'name' => $customer['name'] ?? '',
                        'mobile' => $customer['mobile'] ?? $phone,
                    ];
                }

                $result['token'] = $token;
                $result['profile'] = is_array($profile) ? $profile : [];
                $result['is_continue'] = false; // new registration

                $addresses = $this->apiAuth->getAddresses($token);
                $result['addresses'] = is_array($addresses) ? $addresses : [];
            }
        } catch (\Exception $e) {
            Log::warning('OtpController: external API hydration failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Send OTP code via SMS.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = $validated['phone'];

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

        $storedPhone = session('otp_phone');
        $expiresAt = session('otp_expires_at');

        if (! $storedPhone || ! $expiresAt) {
            return response()->json([
                'success' => false,
                'message' => __('otp.not_found'),
            ]);
        }

        if (now()->isAfter($expiresAt)) {
            session()->forget(['otp_code', 'otp_phone', 'otp_expires_at', 'checkout_otp_external']);

            return response()->json([
                'success' => false,
                'message' => __('otp.expired'),
            ]);
        }

        if ($storedPhone !== $validated['phone']) {
            return response()->json([
                'success' => false,
                'message' => __('otp.phone_mismatch'),
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
                    'success' => false,
                    'message' => $remote['message'] ?? __('otp.invalid_code'),
                ], 422);
            }

            $parsed = $this->parseExternalVerifyBody($remote);

            if ($parsed['token'] === '') {
                Log::warning('External OTP verify returned empty token', ['body_keys' => array_keys($remote)]);

                return response()->json([
                    'success' => false,
                    'message' => __('otp.invalid_code'),
                ], 422);
            }

            session([
                'phone_verified' => $validated['phone'],
                'external_api_token' => $parsed['token'],
                'external_api_profile' => $parsed['profile'],
                'external_login_is_continue' => $parsed['is_continue'],
            ]);
            session()->forget(['otp_code', 'otp_phone', 'otp_expires_at', 'checkout_otp_external']);

            $addresses = $this->apiAuth->getAddresses($parsed['token']);
            if (! is_array($addresses)) {
                $addresses = [];
            }

            return response()->json([
                'success' => true,
                'message' => __('otp.verified'),
                'addresses' => $addresses,
                'profile' => $parsed['profile'],
                'is_continue' => $parsed['is_continue'],
            ]);
        }

        // ─── Path B: Local OTP (we sent the code via SMS) ──────────────
        $storedOtp = session('otp_code');
        if (! $storedOtp) {
            return response()->json([
                'success' => false,
                'message' => __('otp.not_found'),
            ]);
        }

        if ($storedOtp !== $validated['otp']) {
            return response()->json([
                'success' => false,
                'message' => __('otp.invalid_code'),
            ]);
        }

        // Local OTP verified — now hydrate profile/addresses from external API
        $deviceId = $validated['device_id'] ?? null;
        if ($deviceId === null || $deviceId === '') {
            $deviceId = 'web-checkout-' . Str::uuid()->toString();
        }

        $externalData = $this->hydrateFromExternalApi($validated['phone'], $deviceId);

        session([
            'phone_verified' => $validated['phone'],
        ]);

        // Store external API data if we got a token
        if ($externalData['token'] !== '') {
            session([
                'external_api_token' => $externalData['token'],
                'external_api_profile' => $externalData['profile'],
                'external_login_is_continue' => $externalData['is_continue'],
            ]);
        } else {
            session()->forget([
                'external_api_token',
                'external_api_profile',
                'external_login_is_continue',
            ]);
        }

        session()->forget([
            'otp_code',
            'otp_phone',
            'otp_expires_at',
            'checkout_otp_external',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('otp.verified'),
            'addresses' => $externalData['addresses'],
            'profile' => $externalData['profile'],
            'is_continue' => $externalData['is_continue'],
        ]);
    }
}
