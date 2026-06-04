<?php

declare(strict_types=1);

use App\Http\Controllers\Account\AccountAuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\MarketMealController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Livewire\Account\Dashboard;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Subscriptions\Index;
use App\Livewire\Account\Subscriptions\Show;
use App\Livewire\Account\Wallet;
use App\Models\BlogPost;
use App\Models\HowItWorksStep;
use App\Models\Testimonial;
use App\Services\ApiAuthService;
use App\Services\ExternalDataService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $latestPosts = BlogPost::published()
        ->with(['author', 'tags'])
        ->orderBy('published_at', 'desc')
        ->take(4)
        ->get();

    $testimonials = Testimonial::active()
        ->orderBy('order_column')
        ->take(6)
        ->get();

    $howItWorksSteps = HowItWorksStep::active()
        ->ordered()
        ->get();

    // Fetch programs from external API (shown as meal plan categories on homepage)
    $externalDataService = app(ExternalDataService::class);
    $mealPlanCategories = collect($externalDataService->getCategoriesForDisplay())->take(6);

    // Fetch instant order meals — prefer group 29, fallback to catalog
    $instantMeals = $externalDataService->getInstantMealsForHome(12, 29);

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
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// FAQ route
Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');

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
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/apply-coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');

// Subscription routes
Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show'])->name('subscriptions.show');

// AJAX: get plan durations/calories/zones for dynamic checkout
Route::get('/api/plan/{id}/durations', function (int $id) {
    $service = app(ExternalDataService::class);

    return response()->json($service->getCheckoutPlanDurations($id));
})->name('api.plan.durations');

Route::get('/api/plan/{id}/calories', function (int $id) {
    $service = app(ExternalDataService::class);

    return response()->json($service->getPlanCalories($id));
})->name('api.plan.calories');

Route::get('/api/plan/{id}/meals', function (int $id) {
    $service = app(ExternalDataService::class);

    return response()->json($service->getProgramMeals($id));
})->name('api.plan.meals');

Route::get('/api/zones', function () {
    $service = app(ExternalDataService::class);

    return response()->json($service->getZones());
})->name('api.zones');

Route::get('/api/branches', function () {
    $service = app(ExternalDataService::class);

    return response()->json($service->getBranches());
})->name('api.branches');

// Legal pages
Route::get('/privacy-policy', [PageContentController::class, 'privacy'])->name('privacy');
Route::get('/terms-and-conditions', [PageContentController::class, 'terms'])->name('terms');

// OTP verification
Route::post('/otp/send', [OtpController::class, 'send'])
    ->middleware('throttle:10,1')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('otp.send');
Route::post('/otp/verify', [OtpController::class, 'verify'])
    ->middleware('throttle:20,1')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('otp.verify');
Route::post('/otp/register', [OtpController::class, 'register'])
    ->middleware('throttle:5,1')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('otp.register');

Route::post('/checkout/moyasar-session', [CheckoutController::class, 'moyasarSession'])
    ->name('checkout.moyasar-session');

Route::get('/checkout/moyasar-preview-callback', [CheckoutController::class, 'moyasarPreviewCallback'])
    ->name('checkout.moyasar-preview-callback');

Route::post('/checkout/sync-address', [CheckoutController::class, 'syncExternalAddress'])
    ->middleware('throttle:30,1')
    ->name('checkout.sync-address');

Route::post('/checkout/select-address', [CheckoutController::class, 'selectCheckoutAddress'])
    ->middleware('throttle:30,1')
    ->name('checkout.select-address');

Route::get('/checkout/district-durations', [CheckoutController::class, 'districtDeliveryTimes'])
    ->middleware('throttle:60,1')
    ->name('checkout.district-durations');

Route::delete('/checkout/addresses/{addressId}', [CheckoutController::class, 'deleteCheckoutAddress'])
    ->middleware('throttle:20,1')
    ->whereNumber('addressId')
    ->name('checkout.delete-address');

Route::get('/checkout/customer-state', [CheckoutController::class, 'customerState'])
    ->middleware('throttle:60,1')
    ->name('checkout.customer-state');

Route::get('/checkout/subscription-schedule', [CheckoutController::class, 'subscriptionSchedule'])
    ->middleware('throttle:60,1')
    ->name('checkout.subscription-schedule');

Route::get('/checkout/subscription-status', [CheckoutController::class, 'subscriptionStatus'])
    ->middleware('throttle:60,1')
    ->name('checkout.subscription-status');

// Payment routes (Moyasar)
Route::get('/payment', [PaymentController::class, 'form'])->name('payment.form');
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/subscription-confirm', [PaymentController::class, 'subscriptionConfirm'])->name('payment.subscription-confirm');
Route::get('/payment/result', [PaymentController::class, 'result'])->name('payment.result');
Route::get('/payment/invoice', [PaymentController::class, 'downloadInvoice'])->name('payment.invoice');

// ─── AJAX: districts for address picker (Google Maps component) ──────
Route::get('/api/districts', function () {
    return response()->json(app(ApiAuthService::class)->getDistricts());
})->name('api.districts');

// ─── Customer account area ──────────────────────────────────────────
Route::prefix('account')->group(function () {
    Route::get('/login', [AccountAuthController::class, 'showLogin'])->name('account.login');
    Route::post('/logout', [AccountAuthController::class, 'logout'])->middleware('customer.auth')->name('account.logout');

    Route::middleware('customer.auth')->group(function () {
        Route::get('/', Dashboard::class)->name('account.dashboard');
        Route::get('/subscriptions', Index::class)->name('account.subscriptions.index');
        Route::get('/subscriptions/{id}', Show::class)->whereNumber('id')->name('account.subscriptions.show');
        Route::get('/orders', App\Livewire\Account\Orders\Index::class)->name('account.orders.index');
        Route::get('/orders/{id}', App\Livewire\Account\Orders\Show::class)->whereNumber('id')->name('account.orders.show');
        Route::get('/wallet', Wallet::class)->name('account.wallet');
        Route::get('/profile', Profile::class)->name('account.profile');
    });
});
