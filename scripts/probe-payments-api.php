<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$base = rtrim((string) config('services.external_api.url'), '/');

$paths = [
    'payments/callback',
    'payments/confirm',
    'payments/verify',
];

$queries = [
    'empty' => [],
    'moyasar_like' => ['id' => 'test-moyasar-id', 'status' => 'paid'],
    'internal_payment' => ['id' => '38908', 'status' => 'paid', 'payment_id' => '38908'],
    'subscription_payment' => ['id' => 'test-moyasar-id', 'status' => 'paid', 'subscription_id' => '28257', 'payment_id' => '38908'],
];

foreach ($paths as $path) {
    echo "\n=== {$path} ===\n";
    foreach ($queries as $label => $query) {
        try {
            $response = Http::acceptJson()->timeout(15)->get("{$base}/{$path}", $query);
            echo "  [{$label}] HTTP {$response->status()} ".substr(json_encode($response->json()), 0, 200)."\n";
        } catch (Throwable $e) {
            echo "  [{$label}] ERROR {$e->getMessage()}\n";
        }
    }
}
