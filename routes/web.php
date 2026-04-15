<?php

declare(strict_types=1);

use App\Http\Controllers\BlogController;
use App\Http\Controllers\MarketMealController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $latestPosts = \App\Models\BlogPost::published()
        ->with(['author', 'tags'])
        ->orderBy('published_at', 'desc')
        ->take(4)
        ->get();

    $testimonials = \App\Models\Testimonial::active()
        ->orderBy('order_column')
        ->take(6)
        ->get();

    $howItWorksSteps = \App\Models\HowItWorksStep::active()
        ->ordered()
        ->get();

    // Fetch programs from external API (shown as meal plan categories on homepage)
    $externalDataService = app(\App\Services\ExternalDataService::class);
    $mealPlanCategories = collect($externalDataService->getCategoriesForDisplay())->take(6);

    // Fetch instant order meals from /meals API (group_id=29)
    $instantMeals = array_slice($externalDataService->getMealsByGroup(29), 0, 4);

    return view('pages.home', compact('latestPosts', 'testimonials', 'howItWorksSteps', 'mealPlanCategories', 'instantMeals'));
})->name('home');

Route::get('/locale/{locale}', function (string $locale) {
    // Validate locale
    if (! in_array($locale, ['en', 'ar'])) {
        abort(404);
    }

    // Store in session
    session(['locale' => $locale]);

    // Redirect back
    return redirect()->back();
})->name('locale.switch');

// Blog routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Contact routes
Route::get('/contact', [\App\Http\Controllers\ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])->name('contact.store');

// FAQ route
Route::get('/faqs', [\App\Http\Controllers\FaqController::class, 'index'])->name('faqs.index');

// Meal Plans routes
Route::get('/meal-plans', [MealPlanController::class, 'index'])->name('meal-plans.index');
Route::get('/meal-plans/{id}', [MealPlanController::class, 'show'])->name('meal-plans.show');

// Meals (products) route — also accessible as /store (Market link)
Route::get('/store', fn () => view('pages.meals'))->name('store.index');
Route::get('/store/{meal}', [MarketMealController::class, 'show'])
    ->whereNumber('meal')
    ->name('store.show');
Route::get('/meals', fn () => view('pages.meals'))->name('meals.index');
Route::get('/meals/{meal}', [MarketMealController::class, 'show'])
    ->whereNumber('meal')
    ->name('meals.show');

// Checkout routes
Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/apply-coupon', [\App\Http\Controllers\CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');

// Subscription routes
Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show'])->name('subscriptions.show');

// AJAX: get plan durations/calories/zones for dynamic checkout
Route::get('/api/plan/{id}/durations', function (int $id) {
    $service = app(\App\Services\ExternalDataService::class);

    return response()->json($service->getPlanDurations($id));
})->name('api.plan.durations');

Route::get('/api/plan/{id}/calories', function (int $id) {
    $service = app(\App\Services\ExternalDataService::class);

    return response()->json($service->getPlanCalories($id));
})->name('api.plan.calories');

Route::get('/api/zones', function () {
    $service = app(\App\Services\ExternalDataService::class);

    return response()->json($service->getZones());
})->name('api.zones');

Route::get('/api/branches', function () {
    $service = app(\App\Services\ExternalDataService::class);

    return response()->json($service->getBranches());
})->name('api.branches');

// Legal pages
Route::get('/privacy-policy', fn () => view('pages.privacy'))->name('privacy');
Route::get('/terms-and-conditions', fn () => view('pages.terms'))->name('terms');

// OTP verification
Route::post('/otp/send', [\App\Http\Controllers\OtpController::class, 'send'])->name('otp.send');
Route::post('/otp/verify', [\App\Http\Controllers\OtpController::class, 'verify'])->name('otp.verify');

Route::post('/checkout/moyasar-session', [\App\Http\Controllers\CheckoutController::class, 'moyasarSession'])
    ->name('checkout.moyasar-session');

Route::get('/checkout/moyasar-preview-callback', [\App\Http\Controllers\CheckoutController::class, 'moyasarPreviewCallback'])
    ->name('checkout.moyasar-preview-callback');

Route::post('/checkout/sync-address', [\App\Http\Controllers\CheckoutController::class, 'syncExternalAddress'])
    ->middleware('throttle:30,1')
    ->name('checkout.sync-address');

Route::get('/checkout/customer-state', [\App\Http\Controllers\CheckoutController::class, 'customerState'])
    ->middleware('throttle:60,1')
    ->name('checkout.customer-state');

// Payment routes (Moyasar)
Route::get('/payment', [\App\Http\Controllers\PaymentController::class, 'form'])->name('payment.form');
Route::get('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/result', [\App\Http\Controllers\PaymentController::class, 'result'])->name('payment.result');
Route::get('/payment/invoice', [\App\Http\Controllers\PaymentController::class, 'downloadInvoice'])->name('payment.invoice');

// ─── AJAX: districts for address picker (Google Maps component) ──────
Route::get('/api/districts', function () {
    return response()->json(app(\App\Services\ApiAuthService::class)->getDistricts());
})->name('api.districts');
