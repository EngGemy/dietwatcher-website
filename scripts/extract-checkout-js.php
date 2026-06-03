<?php

declare(strict_types=1);

$html = file_get_contents(__DIR__ . '/../storage/checkout-test.html');
if (! is_string($html)) {
    fwrite(STDERR, "Missing storage/checkout-test.html — run render-checkout-test.php first\n");
    exit(1);
}

// Extract checkoutPage script block (last large inline script before window.checkoutPage)
if (! preg_match('/function checkoutPage\(\)\s*\{[\s\S]*?\n    \}\n\n    window\.checkoutPage = checkoutPage;/', $html, $m)) {
    fwrite(STDERR, "Could not extract checkoutPage()\n");
    exit(1);
}

$js = $m[0];
$tmp = __DIR__ . '/../storage/checkout-page-fn.js';
file_put_contents($tmp, $js);

echo "Extracted " . strlen($js) . " bytes to storage/checkout-page-fn.js\n";

// Also extract all x-text attributes with @js-like broken patterns (double-quoted inside double attr)
preg_match_all('/x-text="([^"]*)"/', $html, $attrs);
$broken = [];
foreach ($attrs[1] as $expr) {
    if (preg_match('/"[^"]*"[^+]*\+/', $expr) || preg_match('/\? \("/', $expr)) {
        $broken[] = $expr;
    }
}
if ($broken !== []) {
    echo "Potentially broken x-text expressions:\n";
    foreach (array_slice($broken, 0, 10) as $b) {
        echo "  " . substr($b, 0, 120) . "\n";
    }
}
