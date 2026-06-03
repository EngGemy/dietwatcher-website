<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim((string) config('services.external_api.url'), '/');

$paths = [
    ['DELETE', 'addresses/999999'],
    ['POST', 'addresses/999999', ['_method' => 'DELETE']],
    ['POST', 'addresses/delete', ['id' => 999999]],
    ['POST', 'addresses/999999/delete', []],
    ['GET', 'addresses/999999', []],
];

foreach ($paths as [$method, $path, $payload]) {
    echo "\n=== {$method} {$path} ===\n";
    try {
        $req = Illuminate\Support\Facades\Http::acceptJson()->timeout(15);
        $response = match ($method) {
            'DELETE' => $req->delete("{$base}/{$path}"),
            'POST' => $req->asForm()->post("{$base}/{$path}", $payload),
            default => $req->get("{$base}/{$path}", $payload),
        };
        echo 'HTTP ' . $response->status() . "\n";
        echo substr(json_encode($response->json()), 0, 300) . "\n";
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
}
