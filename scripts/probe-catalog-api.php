<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim((string) config('services.external_api.url'), '/');
$token = (string) config('services.external_api.token');

echo "API: {$base}\n";
echo 'Token set: ' . ($token !== '' ? 'yes (' . strlen($token) . ' chars)' : 'no') . "\n\n";

$paths = ['/programs', '/meals', '/meals/filters', '/program-categories'];

foreach ($paths as $path) {
    echo "=== GET {$path} (no auth) ===\n";
    try {
        $r = Illuminate\Support\Facades\Http::acceptJson()->timeout(25)->get("{$base}{$path}");
        echo 'HTTP ' . $r->status() . "\n";
        $body = $r->json();
        $data = $body['data'] ?? null;
        $count = is_array($data) ? count($data) : 0;
        echo "data count: {$count}\n";
        if ($r->failed()) {
            echo substr($r->body(), 0, 300) . "\n";
        }
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
    echo "\n";
}

foreach ($paths as $path) {
    if ($token === '') {
        break;
    }
    echo "=== GET {$path} (with token) ===\n";
    try {
        $r = Illuminate\Support\Facades\Http::withToken($token)->acceptJson()->timeout(25)->get("{$base}{$path}");
        echo 'HTTP ' . $r->status() . "\n";
        $body = $r->json();
        $data = $body['data'] ?? null;
        $count = is_array($data) ? count($data) : 0;
        echo "data count: {$count}\n";
        if ($r->failed()) {
            echo substr($r->body(), 0, 300) . "\n";
        }
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
    echo "\n";
}

$service = app(App\Services\ExternalDataService::class);
Illuminate\Support\Facades\Cache::flush();
echo "=== ExternalDataService (cache flushed) ===\n";
echo 'programs: ' . count($service->getPrograms()) . "\n";
$meals = $service->getMeals(['page' => 1]);
echo 'meals: ' . count($meals['data'] ?? []) . "\n";
$filters = $service->getMealFilters();
echo 'filter groups: ' . count($filters['groups'] ?? []) . "\n";
