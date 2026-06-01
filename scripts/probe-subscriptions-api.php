<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim((string) config('services.external_api.url'), '/');
$token = (string) config('services.external_api.token');

$http = Illuminate\Support\Facades\Http::withToken($token)
    ->acceptJson()
    ->asForm()
    ->timeout(20);

function probe(string $label, callable $fn): void
{
    echo "\n=== {$label} ===\n";
    try {
        $response = $fn();
        echo 'HTTP ' . $response->status() . "\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
}

probe('POST /subscriptions/calculate (empty)', fn () => $http->post("{$base}/subscriptions/calculate", []));

probe('POST /subscriptions (empty)', fn () => $http->post("{$base}/subscriptions", []));

// Partial payload from checkout promo validation
$partial = [
    'program_id' => '1',
    'plan_id' => '1',
    'plan_duration_id' => '1',
    'plan_calory_id' => '1',
    'promocode_name' => '',
    'receiving' => 'delivery',
    'with_support' => '0',
    'with_weekend' => '0',
];

probe('POST /subscriptions/calculate (partial)', fn () => $http->post("{$base}/subscriptions/calculate", $partial));

// Try to get a real program for better probe
$programs = Illuminate\Support\Facades\Http::withToken($token)
    ->acceptJson()
    ->get("{$base}/programs")
    ->json('data', []);

if (is_array($programs) && $programs !== []) {
    $programId = (int) ($programs[0]['id'] ?? 0);
    echo "\n=== First program id: {$programId} ===\n";

    $detail = Illuminate\Support\Facades\Http::withToken($token)
        ->acceptJson()
        ->get("{$base}/programs/{$programId}")
        ->json('data', []);

    $planId = (int) data_get($detail, 'plans.0.id', 0);
    $durationId = (int) data_get($detail, 'plans.0.durations.0.id', 0);
    $caloryId = (int) data_get($detail, 'plans.0.calories.0.id', 0);

    echo "plan_id={$planId} duration_id={$durationId} calory_id={$caloryId}\n";

    $rich = [
        'program_id' => (string) $programId,
        'plan_id' => (string) ($planId ?: $programId),
        'plan_duration_id' => (string) $durationId,
        'plan_calory_id' => (string) $caloryId,
        'receiving' => 'delivery',
        'with_support' => '0',
        'with_weekend' => '0',
        'address_id' => '',
        'branch_id' => '',
        'payment_option' => 'credit_card',
        'start_date' => date('Y-m-d', strtotime('+2 days')),
    ];

    probe('POST /subscriptions/calculate (rich)', fn () => $http->post("{$base}/subscriptions/calculate", $rich));
    probe('POST /subscriptions (rich, no auth customer)', fn () => $http->post("{$base}/subscriptions", $rich));
}

$getHttp = Illuminate\Support\Facades\Http::withToken($token)->acceptJson()->timeout(20);
foreach (['/programs', '/orders', '/subscriptions'] as $path) {
    probe('GET ' . $path . ' (app token)', fn () => $getHttp->get("{$base}{$path}"));
}

// Probe orders POST validation (app token)
probe('POST /orders (empty, app token)', fn () => $getHttp->asForm()->post("{$base}/orders", []));
