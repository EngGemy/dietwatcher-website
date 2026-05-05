<?php

declare(strict_types=1);

/**
 * Builds lang/ar/validation.php from lang/en/validation.php + Laravel-Lang ar/php.json
 */
$base = dirname(__DIR__);
$en = require $base.'/lang/en/validation.php';
$jsonPath = $base.'/storage/framework/cache/ar-validation-php.json';
if (! is_file($jsonPath)) {
    $src = file_get_contents('https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/ar/php.json');
    if ($src === false) {
        fwrite(STDERR, "Failed to download php.json\n");
        exit(1);
    }
    if (! is_dir(dirname($jsonPath))) {
        mkdir(dirname($jsonPath), 0755, true);
    }
    file_put_contents($jsonPath, $src);
}

/** @var array<string, mixed> $json */
$json = json_decode(file_get_contents($jsonPath), true);
if (! is_array($json)) {
    fwrite(STDERR, "Invalid JSON\n");
    exit(1);
}

/**
 * @param  array<string, mixed>|string  $val
 * @return array<string, mixed>|string
 */
function translateBranch(string $prefix, mixed $val, array $json): mixed
{
    if (is_string($val)) {
        return $json[$prefix] ?? $val;
    }
    if (! is_array($val)) {
        return $val;
    }
    $out = [];
    foreach ($val as $subKey => $subVal) {
        $dotKey = "{$prefix}.{$subKey}";
        if (is_array($subVal)) {
            $out[$subKey] = translateBranch($dotKey, $subVal, $json);
        } else {
            $out[$subKey] = $json[$dotKey] ?? $subVal;
        }
    }

    return $out;
}

$ar = [];
foreach ($en as $k => $v) {
    if (in_array($k, ['custom', 'attributes'], true)) {
        $ar[$k] = $v;

        continue;
    }
    if (is_array($v)) {
        $ar[$k] = translateBranch((string) $k, $v, $json);
    } else {
        $ar[$k] = $json[$k] ?? $v;
    }
}

$ar['attributes'] = array_merge([
    'identifier' => 'رقم الجوال',
    'phone' => 'رقم الجوال',
    'code' => 'كود الخصم',
    'subtotal' => 'المجموع الفرعي',
    'email' => 'البريد الإلكتروني',
    'name' => 'الاسم',
    'coupon' => 'كود الخصم',
], is_array($ar['attributes']) ? $ar['attributes'] : []);

$stub = "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($ar, true).";\n";
file_put_contents($base.'/lang/ar/validation.php', $stub);
echo "OK: lang/ar/validation.php\n";
