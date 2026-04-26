@extends('layouts.app')

@section('title', ($dynamicTitle ?? __('Privacy Policy')) . ' - ' . config('app.name'))

@section('content')
    <section class="bg-gray-200 pt-20 pb-28">
        <div class="container">
            <header class="section-header max-w-3xl">
                <h2 class="section-header__title">{{ $dynamicTitle ?? __('Privacy Policy') }}</h2>
                <p class="section-header__desc">
                    {{ $dynamicExcerpt ?: __('Your privacy matters to us. This policy explains how we collect, use, and protect your personal information.') }}
                </p>
            </header>

            <div class="mx-auto max-w-4xl rounded-md bg-white p-6 md:p-10 text-start">
                <div class="legal-body max-w-none space-y-5 text-gray-800 [&_h3]:mt-8 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-gray-900 [&_h4]:mt-4 [&_h4]:font-semibold [&_h4]:text-gray-900 [&_p]:mb-3 [&_p]:leading-relaxed [&_ul]:my-3 [&_ul]:list-disc [&_ul]:ps-5 [&_li]:my-1 [&_a]:text-blue-600 [&_a]:underline">
                    @if(!empty($dynamicHtml))
                        {!! $dynamicHtml !!}
                    @else

                    <h3>{{ __('Introduction') }}</h3>
                    <p>{{ __('Diet Watchers ("we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and mobile application.') }}</p>

                    <h3>{{ __('Information We Collect') }}</h3>
                    <h4>{{ __('Personal Information') }}</h4>
                    <ul>
                        <li>{{ __('Name, email address, and phone number') }}</li>
                        <li>{{ __('Delivery address and location data') }}</li>
                        <li>{{ __('Payment information (processed securely through our payment providers)') }}</li>
                        <li>{{ __('Health and dietary preferences you provide') }}</li>
                    </ul>

                    <h4>{{ __('Automatically Collected Information') }}</h4>
                    <ul>
                        <li>{{ __('Device information (type, operating system, browser)') }}</li>
                        <li>{{ __('Usage data (pages visited, features used, time spent)') }}</li>
                        <li>{{ __('IP address and approximate location') }}</li>
                        <li>{{ __('Cookies and similar tracking technologies') }}</li>
                    </ul>

                    <h3>{{ __('How We Use Your Information') }}</h3>
                    <ul>
                        <li>{{ __('To process and deliver your meal plan orders') }}</li>
                        <li>{{ __('To personalize your meal recommendations based on your dietary goals') }}</li>
                        <li>{{ __('To communicate with you about your orders, account, and promotions') }}</li>
                        <li>{{ __('To improve our services, website, and mobile application') }}</li>
                        <li>{{ __('To comply with legal obligations') }}</li>
                    </ul>

                    <h3>{{ __('Data Sharing') }}</h3>
                    <p>{{ __('We do not sell your personal information. We may share your data with:') }}</p>
                    <ul>
                        <li>{{ __('Delivery partners to fulfill your orders') }}</li>
                        <li>{{ __('Payment processors to handle transactions securely') }}</li>
                        <li>{{ __('Analytics providers to help us improve our services') }}</li>
                        <li>{{ __('Legal authorities when required by law') }}</li>
                    </ul>

                    <h3>{{ __('Data Security') }}</h3>
                    <p>{{ __('We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the Internet is 100% secure.') }}</p>

                    <h3>{{ __('Your Rights') }}</h3>
                    <p>{{ __('You have the right to:') }}</p>
                    <ul>
                        <li>{{ __('Access and receive a copy of your personal data') }}</li>
                        <li>{{ __('Correct inaccurate or incomplete data') }}</li>
                        <li>{{ __('Request deletion of your personal data') }}</li>
                        <li>{{ __('Withdraw consent for data processing') }}</li>
                        <li>{{ __('Object to processing of your personal data') }}</li>
                    </ul>

                    <h3>{{ __('Cookies') }}</h3>
                    <p>{{ __('We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. You can manage your cookie preferences through your browser settings.') }}</p>

                    <h3>{{ __('Changes to This Policy') }}</h3>
                    <p>{{ __('We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the effective date.') }}</p>

                    <h3>{{ __('Contact Us') }}</h3>
                    <p>{{ __('If you have any questions about this Privacy Policy, please contact us at:') }}</p>
                    <ul>
                        <li>{{ __('Email') }}: <a href="mailto:info@diet-watchers.sa">info@diet-watchers.sa</a></li>
                        <li>{{ __('Phone') }}: <a href="tel:+966920015428">(966) 920015428</a></li>
                    </ul>
                    @endif

                </div>
            </div>
        </div>
    </section>
@endsection
