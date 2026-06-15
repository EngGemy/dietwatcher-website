<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        if (filter_var(env('BLOG_DAILY_GENERATION', true), FILTER_VALIDATE_BOOL)) {
            $time = (string) env('BLOG_DAILY_TIME', '08:00');
            $schedule->command('blog:generate-daily')
                ->dailyAt($time)
                ->withoutOverlapping()
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/blog-generation.log'));
        }
    })
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
