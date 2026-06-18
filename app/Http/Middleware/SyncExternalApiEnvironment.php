<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ExternalApiConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps catalog cache and customer API sessions aligned with EXTERNAL_API_URL.
 */
class SyncExternalApiEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        ExternalApiConfig::ensureCacheSyncedWithEnvironment();
        ExternalApiConfig::clearMismatchedSession();

        return $next($request);
    }
}
