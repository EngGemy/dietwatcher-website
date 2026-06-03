<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

view()->share('errors', new Illuminate\Support\ViewErrorBag);

$html = view('pages.checkout', [
    'cart' => ['plan_13' => [
        'id' => 13,
        'name' => 'Test Plan',
        'price' => 4430.72,
        'image' => '',
        'quantity' => 1,
        'options' => ['duration_days' => 28, 'duration_id' => '5', 'calories' => '1200'],
    ]],
    'baseSubtotal' => 4430.72,
    'deliveryFeeAmount' => 0.0,
    'vatRate' => 0.15,
    'zones' => [['id' => 1, 'name' => 'Riyadh', 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true]],
    'durationMultipliers' => ['once' => 1, 'weekly' => 0.25, 'monthly' => 1, '3months' => 3],
    'planDurations' => [['id' => 5, 'days' => 28, 'price' => 4430, 'offer_price' => 0, 'effective_price' => 4430.72, 'price_per_day' => 23.14, 'label' => ['en' => '28 days', 'ar' => '28 يوم']]],
    'planDurationPrices' => ['5' => 4430.72],
    'selectedDurationLabel' => '28 days',
    'selectedDurationIdFromCart' => '5',
    'preferredPlanDurationId' => '5',
    'checkoutProgramId' => 13,
    'cartDurationFallback' => null,
    'defaultStartDate' => '2026-06-03',
    'minStartDate' => '2026-06-03',
    'siteName' => 'Diet Watchers',
])->render();

$out = storage_path('checkout-test.html');
file_put_contents($out, $html);

echo "Wrote {$out} (" . strlen($html) . " bytes)\n";

// Extract inline scripts and validate attribute patterns
if (preg_match_all('/x-text="[^"]*@js[^"]*"/', $html, $m)) {
    echo "Found x-text with embedded quotes from @js (BROKEN):\n";
    foreach ($m[0] as $line) {
        echo '  ' . substr($line, 0, 120) . "\n";
    }
}

if (preg_match_all('/x-text="[^"]*"\s*\+\s*\'/', $html, $m2)) {
    echo "Sample x-text attrs:\n";
}

// Find lines with unbalanced quotes in x-text containing JSON strings
foreach (explode("\n", $html) as $num => $line) {
    if (str_contains($line, 'x-text=') && preg_match('/x-text="[^"]*"[^>]*\+/', $line)) {
        if (preg_match_all('/"/', $line) && substr_count($line, '"') > 2) {
            echo 'Line ' . ($num + 1) . ': ' . substr(trim($line), 0, 200) . "\n";
        }
    }
}
