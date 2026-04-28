<?php

namespace App\Http\Controllers;

use App\Services\ExternalDataService;

class PageContentController extends Controller
{
    public function privacy()
    {
        $service = app(ExternalDataService::class);
        $page = $service->getPageByAlias([
            'privacy-policy',
            'privacy_policy',
            'privacy policy',
            'privacy',
        ]);

        return view('pages.privacy', [
            'dynamicTitle' => $page['title'] ?? __('Privacy Policy'),
            'dynamicExcerpt' => $page['excerpt'] ?? '',
            'dynamicHtml' => $page['content_html'] ?? '',
        ]);
    }

    public function terms()
    {
        $service = app(ExternalDataService::class);
        $page = $service->getPageByAlias([
            'terms-and-conditions',
            'terms_and_conditions',
            'terms conditions',
            'terms',
        ]);

        return view('pages.terms', [
            'dynamicTitle' => $page['title'] ?? __('Terms & Conditions'),
            'dynamicExcerpt' => $page['excerpt'] ?? '',
            'dynamicHtml' => $page['content_html'] ?? '',
        ]);
    }
}

