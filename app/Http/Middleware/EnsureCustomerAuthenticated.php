<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the /account/* area. Redirects unauthenticated requests to the
 * phone-OTP login page, remembering where the user was trying to go.
 */
class EnsureCustomerAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->session()->get('external_api_token', '');
        $phone = (string) $request->session()->get('phone_verified', '');

        if ($token === '' || $phone === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('account.login_required'),
                ], 401);
            }

            $request->session()->put('account.intended_url', $request->fullUrl());

            return redirect()->route('account.login');
        }

        return $next($request);
    }
}
