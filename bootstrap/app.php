<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Checkout OTP is sent via AJAX and can fail with CSRF mismatch on some
        // production edge setups (proxy/domain/cookie scope differences).
        // We exempt only OTP endpoints and keep protection elsewhere.
        $middleware->validateCsrfTokens(except: [
            'otp/*',
            '/otp/*',
            'otp/send',
            'otp/verify',
            '/otp/send',
            '/otp/verify',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'customer.auth' => \App\Http\Middleware\EnsureCustomerAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
