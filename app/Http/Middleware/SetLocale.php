<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale based on session.
 *
 * Reads locale from session (fallback to config), sets app locale.
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $default = config('app.locale', 'ar');
        $locale = session('locale', $default);

        // Ensure locale is valid (en or ar only)
        if (!in_array($locale, ['en', 'ar'], true)) {
            $locale = $default;
        }

        app()->setLocale($locale);
        
        return $next($request);
    }
}
