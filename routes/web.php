<?php

declare(strict_types=1);

use App\Http\Controllers\BlogController;
use App\Http\Controllers\MealPlanController;
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
    $instantMeals = $externalDataService->getMealsByGroup(29);

    return view('pages.home', compact('latestPosts', 'testimonials', 'howItWorksSteps', 'mealPlanCategories', 'instantMeals'));
})->name('home');

Route::get('/locale/{locale}', function (string $locale) {
    // Validate locale
    if (!in_array($locale, ['en', 'ar'])) {
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

// Meals (products) route
Route::get('/meals', fn () => view('pages.meals'))->name('meals.index');

// Checkout routes
Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/apply-coupon', [\App\Http\Controllers\CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');

// OTP verification
Route::post('/otp/send', [\App\Http\Controllers\OtpController::class, 'send'])->name('otp.send');
Route::post('/otp/verify', [\App\Http\Controllers\OtpController::class, 'verify'])->name('otp.verify');

// Payment routes (Moyasar)
Route::get('/payment', [\App\Http\Controllers\PaymentController::class, 'form'])->name('payment.form');
Route::get('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/result', [\App\Http\Controllers\PaymentController::class, 'result'])->name('payment.result');
