<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    /**
     * Send OTP code via SMS.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = $validated['phone'];

        // Rate limit: max 1 OTP per 60 seconds per phone
        $sessionKey = 'otp_sent_at_' . md5($phone);
        $lastSentAt = session($sessionKey);

        if ($lastSentAt && (time() - $lastSentAt) < 60) {
            $remaining = 60 - (time() - $lastSentAt);
            return response()->json([
                'success' => false,
                'message' => __('otp.wait_seconds', ['seconds' => $remaining]),
            ], 429);
        }

        // Generate 4-digit OTP
        $otp = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Store in session (expires in 5 minutes)
        session([
            'otp_code' => $otp,
            'otp_phone' => $phone,
            'otp_expires_at' => now()->addMinutes(5),
            $sessionKey => time(),
        ]);

        // Send SMS
        $message = __('sms.otp_code', ['code' => $otp]);

        try {
            SmsService::create()->send($phone, $message);
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

        // In non-production, return OTP for testing
        $response = [
            'success' => true,
            'message' => __('otp.code_sent'),
        ];

        if (!app()->isProduction()) {
            $response['otp'] = $otp;
        }

        return response()->json($response);
    }

    /**
     * Verify OTP code.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'otp' => 'required|string|size:4',
        ]);

        $storedOtp = session('otp_code');
        $storedPhone = session('otp_phone');
        $expiresAt = session('otp_expires_at');

        if (!$storedOtp || !$storedPhone || !$expiresAt) {
            return response()->json([
                'success' => false,
                'message' => __('otp.not_found'),
            ]);
        }

        if (now()->isAfter($expiresAt)) {
            session()->forget(['otp_code', 'otp_phone', 'otp_expires_at']);
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

        if ($storedOtp !== $validated['otp']) {
            return response()->json([
                'success' => false,
                'message' => __('otp.invalid_code'),
            ]);
        }

        // Mark phone as verified in session
        session([
            'phone_verified' => $validated['phone'],
        ]);
        session()->forget(['otp_code', 'otp_phone', 'otp_expires_at']);

        return response()->json([
            'success' => true,
            'message' => __('otp.verified'),
        ]);
    }
}
