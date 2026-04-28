<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\ApiAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountAuthController extends Controller
{
    public function __construct(
        private ApiAuthService $apiAuth
    ) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('external_api_token') && $request->session()->get('phone_verified')) {
            return redirect()->route('account.dashboard');
        }

        return view('account.login');
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('external_api_token', '');
        if ($token !== '') {
            $this->apiAuth->logout($token);
        }

        $request->session()->forget([
            'external_api_token',
            'external_api_profile',
            'external_login_is_continue',
            'phone_verified',
            'account.intended_url',
        ]);

        return redirect()->route('home')->with('status', __('account.logged_out'));
    }
}
