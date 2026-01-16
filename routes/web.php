<?php

use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\CustomerRegistrationController;
use App\Http\Controllers\JobsListingController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::view('/privacy', 'privacy')->name('privacy');

Route::view('/terms', 'terms')->name('terms');

Route::get('/customer/login', [CustomerLoginController::class, 'create'])
    ->name('filament.customer.auth.login');
Route::post('/customer/login', [CustomerLoginController::class, 'store'])
    ->name('customer.login.store');

Route::get('/customer/register', [CustomerRegistrationController::class, 'create'])
    ->name('customer.register');
Route::post('/customer/register', [CustomerRegistrationController::class, 'store'])
    ->name('customer.register.store');

// Jobs
Route::get('/jobs', [JobsListingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{post}', [JobsListingController::class, 'show'])->name('jobs.show');

// Free posting shortcut – protects the route with auth + a middleware that ensures the user
// is allowed to create free postings. When allowed, it marks the session so the create form
// knows to operate in the free flow and redirects to the Filament resource create page.
Route::get('/customer/free/create', function () {
    // Redirect explicitly to the customer panel's create route (avoid defaulting to admin)
    return redirect()->route('filament.customer.resources.posts.create');
})->middleware(['auth', \App\Http\Middleware\EnsureUserHasFreeAccess::class])
    ->name('customer.posts.create.free');

// Stripe webhook (no CSRF)
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('webhooks.stripe');

// Payment success callback (placeholder)
Route::get('/posts/payment/success', function () {
    return response('Payment success', 200);
})->name('posts.payment.success');

// Minimal success URL for Stripe to redirect to after payment
Route::get('/posts/payment/success', function (\Illuminate\Http\Request $request) {
    return response('Payment success', 200);
})->name('posts.payment.success');
