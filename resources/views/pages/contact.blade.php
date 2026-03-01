@extends('layouts.app')

@section('title', __('Contact Us'))

@section('content')
    <section class="bg-gray-200 pt-20 pb-28">
        <div class="container mb-10 md:mb-16">
            
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-100 p-4 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <header class="section-header max-w-3xl">
                <h2 class="section-header__title">{{ __('Get in Touch') }}</h2>
                <p class="section-header__desc">
                    {{ __('We\'re here to help. Reach out to us with any questions, feedback, or support needs.') }}
                </p>
            </header>

            <div class="flex flex-col gap-8 md:flex-row">
                {{-- Contact Form --}}
                <div class="flex-1">
                    <div class="rounded-md bg-white p-6 md:p-10">
                        <form action="{{ route('contact.store') }}" method="POST">
                            @csrf
                            <div class="mb-8 flex flex-col gap-5">
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label for="first_name" class="form-label">{{ __('First Name') }}</label>
                                        <input
                                            type="text"
                                            id="first_name"
                                            name="first_name"
                                            class="form-control @error('first_name') border-red-500 @enderror"
                                            placeholder="{{ __('Add your first name') }}"
                                            value="{{ old('first_name') }}"
                                            required
                                        />
                                        @error('first_name')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="last_name" class="form-label">{{ __('Last Name') }}</label>
                                        <input
                                            type="text"
                                            id="last_name"
                                            name="last_name"
                                            class="form-control @error('last_name') border-red-500 @enderror"
                                            placeholder="{{ __('Add your last name') }}"
                                            value="{{ old('last_name') }}"
                                            required
                                        />
                                        @error('last_name')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label for="email" class="form-label">{{ __('Email') }}</label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            class="form-control @error('email') border-red-500 @enderror"
                                            placeholder="{{ __('Add your email') }}"
                                            value="{{ old('email') }}"
                                            required
                                        />
                                        @error('email')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="subject" class="form-label">{{ __('Subject') }}</label>
                                        <select
                                            name="subject"
                                            id="subject"
                                            class="form-control @error('subject') border-red-500 @enderror"
                                            required
                                        >
                                            <option value="">{{ __('Select a subject') }}</option>
                                            <option value="Support" {{ old('subject') == 'Support' ? 'selected' : '' }}>{{ __('Support') }}</option>
                                            <option value="Feedback" {{ old('subject') == 'Feedback' ? 'selected' : '' }}>{{ __('Feedback') }}</option>
                                            <option value="Business Inquiry" {{ old('subject') == 'Business Inquiry' ? 'selected' : '' }}>{{ __('Business Inquiry') }}</option>
                                            <option value="Other" {{ old('subject') == 'Other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                        </select>
                                        @error('subject')
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="message" class="form-label">{{ __('Message') }}</label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        class="form-control @error('message') border-red-500 @enderror"
                                        placeholder="{{ __('Type a message...') }}"
                                        rows="5"
                                        required
                                    >{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn--primary btn--md self-start">
                                {{ __('Submit Message') }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Contact Info --}}
                <div class="w-full md:max-w-xl">
                    <div class="rounded-md bg-white p-6 md:p-10">
                        <h3 class="mb-8 text-2xl font-semibold md:text-3xl">{{ __('Contact Us') }}</h3>

                        <div class="space-y-6">
                            <div class="flex items-center gap-4 rounded-md bg-gray-200 p-5">
                                <div class="inline-flex size-12 items-center justify-center rounded-sm border border-gray-300">
                                    <svg class="size-7">
                                        <use href="{{ asset('assets/images/icons/sprite.svg#envlob') }}"></use>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold">{{ __('Email Address') }}</h4>
                                    <a href="mailto:{{ $contactEmail ?: 'info@diet-watchers.com' }}">{{ $contactEmail ?: 'info@diet-watchers.com' }}</a>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 rounded-md bg-gray-200 p-5">
                                <div class="inline-flex size-12 items-center justify-center rounded-sm border border-gray-300">
                                    <svg class="size-7">
                                        <use href="{{ asset('assets/images/icons/sprite.svg#phone-2') }}"></use>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold">{{ __('Phone Number') }}</h4>
                                    <a href="tel:+966920015428">(966) 920015428</a>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 rounded-md bg-gray-200 p-5">
                                <div class="inline-flex size-12 items-center justify-center rounded-sm border border-gray-300">
                                    <svg class="size-7">
                                        <use href="{{ asset('assets/images/icons/sprite.svg#location') }}"></use>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold">{{ __('Address') }}</h4>
                                    <p>{{ __('Riyadh, Saudi Arabia') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Map --}}
        <div>
            <iframe
                class="h-[500px] w-full"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d463877.3124244862!2d46.492886522404916!3d24.725455373793224!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2f03890d489399%3A0xba974d1c98e79fd5!2sRiyadh%20Saudi%20Arabia!5e0!3m2!1sen!2seg!4v1770902593711!5m2!1sen!2seg"
                style="border: 0"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </div>
    </section>
@endsection
