@extends('layouts.app')

@section('title', ($dynamicTitle ?? __('Terms & Conditions')) . ' - ' . config('app.name'))

@section('content')
    <section class="bg-gray-200 pt-20 pb-28">
        <div class="container">
            <header class="section-header max-w-3xl">
                <h2 class="section-header__title">{{ $dynamicTitle ?? __('Terms & Conditions') }}</h2>
                <p class="section-header__desc">
                    {{ $dynamicExcerpt ?: __('Please read these terms carefully before using our services. By using Diet Watchers, you agree to these terms.') }}
                </p>
            </header>

            <div class="mx-auto max-w-4xl rounded-md bg-white p-6 md:p-10 text-start">
                <div class="legal-body max-w-none space-y-5 text-gray-800 [&_h3]:mt-8 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-gray-900 [&_h4]:mt-4 [&_h4]:font-semibold [&_h4]:text-gray-900 [&_p]:mb-3 [&_p]:leading-relaxed [&_ul]:my-3 [&_ul]:list-disc [&_ul]:ps-5 [&_li]:my-1 [&_a]:text-blue-600 [&_a]:underline">
                    @if(!empty($dynamicHtml))
                        {!! $dynamicHtml !!}
                    @else

                    <h3>{{ __('Acceptance of Terms') }}</h3>
                    <p>{{ __('By accessing or using the Diet Watchers website and mobile application, you agree to be bound by these Terms & Conditions. If you do not agree to these terms, please do not use our services.') }}</p>

                    <h3>{{ __('Services') }}</h3>
                    <p>{{ __('Diet Watchers provides personalized meal plan subscriptions and individual meal orders delivered to your location. Our services include:') }}</p>
                    <ul>
                        <li>{{ __('Customized meal plans based on your dietary goals') }}</li>
                        <li>{{ __('Individual meal orders from our Market') }}</li>
                        <li>{{ __('Nutritional guidance and meal recommendations') }}</li>
                        <li>{{ __('Scheduled meal delivery services') }}</li>
                    </ul>

                    <h3>{{ __('Account Registration') }}</h3>
                    <ul>
                        <li>{{ __('You must provide accurate and complete information when creating an account') }}</li>
                        <li>{{ __('You are responsible for maintaining the security of your account credentials') }}</li>
                        <li>{{ __('You must be at least 18 years old to use our services') }}</li>
                        <li>{{ __('We reserve the right to suspend or terminate accounts that violate these terms') }}</li>
                    </ul>

                    <h3>{{ __('Orders & Payments') }}</h3>
                    <ul>
                        <li>{{ __('All prices are displayed in Saudi Riyal (SAR) and include applicable taxes') }}</li>
                        <li>{{ __('Payment must be completed at the time of order placement') }}</li>
                        <li>{{ __('We accept payments through approved payment methods displayed at checkout') }}</li>
                        <li>{{ __('Order confirmation will be sent to your registered email or phone number') }}</li>
                    </ul>

                    <h3>{{ __('Subscriptions') }}</h3>
                    <ul>
                        <li>{{ __('Meal plan subscriptions are billed according to the selected plan duration') }}</li>
                        <li>{{ __('Subscriptions auto-renew unless cancelled before the renewal date') }}</li>
                        <li>{{ __('Changes to your subscription can be made through your account or by contacting support') }}</li>
                        <li>{{ __('Subscription prices may be updated with prior notice') }}</li>
                    </ul>

                    <h3>{{ __('Delivery') }}</h3>
                    <ul>
                        <li>{{ __('Delivery is available within our designated service areas') }}</li>
                        <li>{{ __('Delivery times are estimates and may vary due to external factors') }}</li>
                        <li>{{ __('You are responsible for providing accurate delivery address information') }}</li>
                        <li>{{ __('We are not liable for delays caused by incorrect address information or inaccessible locations') }}</li>
                    </ul>

                    <h3>{{ __('Cancellation & Refunds') }}</h3>
                    <ul>
                        <li>{{ __('Orders may be cancelled up to the cutoff time specified for each delivery') }}</li>
                        <li>{{ __('Refund eligibility depends on the cancellation timing and order status') }}</li>
                        <li>{{ __('Refunds will be processed through the original payment method') }}</li>
                        <li>{{ __('Processing time for refunds may take 5-14 business days') }}</li>
                    </ul>

                    <h3>{{ __('Allergens & Dietary Information') }}</h3>
                    <p>{{ __('While we take care to accommodate dietary preferences and allergies, we prepare meals in facilities that handle common allergens. It is your responsibility to review meal ingredients and inform us of any severe allergies. We cannot guarantee a completely allergen-free environment.') }}</p>

                    <h3>{{ __('Intellectual Property') }}</h3>
                    <p>{{ __('All content on the Diet Watchers website and application, including text, images, logos, and designs, is the property of Diet Watchers and is protected by intellectual property laws. You may not reproduce, distribute, or use our content without prior written permission.') }}</p>

                    <h3>{{ __('Limitation of Liability') }}</h3>
                    <p>{{ __('Diet Watchers provides nutritional meal services and is not a medical provider. Our meal plans are not intended to diagnose, treat, or cure any medical condition. Consult a healthcare professional before making significant dietary changes. We are not liable for any health issues arising from the use of our services.') }}</p>

                    <h3>{{ __('Governing Law') }}</h3>
                    <p>{{ __('These Terms & Conditions are governed by and construed in accordance with the laws of the Kingdom of Saudi Arabia. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts in Saudi Arabia.') }}</p>

                    <h3>{{ __('Changes to Terms') }}</h3>
                    <p>{{ __('We reserve the right to update these Terms & Conditions at any time. Continued use of our services after changes constitutes acceptance of the updated terms.') }}</p>

                    <h3>{{ __('Contact Us') }}</h3>
                    <p>{{ __('If you have any questions about these Terms & Conditions, please contact us at:') }}</p>
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
