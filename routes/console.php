<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('external:clear-catalog-cache', function () {
    app(\App\Services\ExternalDataService::class)->clearCache();
    $this->info('External catalog cache cleared (programs, meals, categories).');
})->purpose('Clear cached meal plans and store data from the external API');
