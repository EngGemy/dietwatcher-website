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

            $addresses = $this->apiAuth->getAddresses($parsed['token'], true);
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

        session([
            'phone_verified' => $validated['phone'],
        ]);
        session()->forget([
            'otp_code',
            'otp_phone',
            'otp_expires_at',
            'checkout_otp_external',
            'external_api_token',
            'external_api_profile',
            'external_login_is_continue',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('otp.verified'),
            'addresses' => [],
            'profile' => [],
            'is_continue' => false,
        ]);
    }
}
