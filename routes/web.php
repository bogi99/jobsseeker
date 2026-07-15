<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\CustomerRegistrationController;
use App\Http\Controllers\JobsListingController;
use App\Http\Controllers\PostPaymentController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\WelcomeController;
use App\Http\Middleware\EnsureUserHasFreeAccess;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Temporary: catch POSTs to root to detect misconfigured webhook endpoints (logs and returns 200)

Route::post('/', function (Request $request) {
    Log::warning('Received POST to root — likely misconfigured webhook endpoint', [
        'ip' => $request->ip(),
        'headers' => array_intersect_key($request->headers->all(), array_flip(['stripe-signature', 'content-type'])),
        'body' => substr($request->getContent(), 0, 2000),
    ]);

    return response('OK', 200);
})->withoutMiddleware(VerifyCsrfToken::class);

Route::get('/privacy', [PrivacyController::class, 'index'])->name('privacy');

Route::get('/terms', [TermsController::class, 'index'])->name('terms');

Route::get('/about', [AboutController::class, 'index'])->name('about');

Route::get('/contact', [PublicContactController::class, 'index'])->name('contact.form');
Route::post('/contact', [PublicContactController::class, 'submit'])->name('contact.submit');

Route::get('/customer/login', [CustomerLoginController::class, 'create'])
    ->name('filament.customer.auth.login');
Route::post('/customer/login', [CustomerLoginController::class, 'store'])
    ->name('customer.login.store');

// Provide a default `/login` route so Laravel's `auth` middleware can redirect
// unauthenticated users to a working login page (used by email verification flow).
Route::get('/login', [CustomerLoginController::class, 'create'])->name('login');

Route::get('/customer/register', [CustomerRegistrationController::class, 'create'])
    ->name('customer.register');
Route::post('/customer/register', [CustomerRegistrationController::class, 'store'])
    ->name('customer.register.store');

// Email verification routes (Laravel-style)

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('jobs.index')->with('status', 'Your email has been verified.');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
// Jobs
Route::get('/jobs', [JobsListingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{post}', [JobsListingController::class, 'show'])->name('jobs.show');

// Free posting shortcut – protects the route with auth + a middleware that ensures the user
// is allowed to create free postings. When allowed, it marks the session so the create form
// knows to operate in the free flow and redirects to the Filament resource create page.
Route::get('/customer/create', function () {
    // Redirect explicitly to the customer panel's create route (avoid defaulting to admin)
    return redirect()->route('filament.customer.resources.posts.create');
})->middleware(['auth', EnsureUserHasFreeAccess::class])
    ->name('customer.posts.create.free');

// Stripe webhook (no CSRF)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('webhooks.stripe');

// Helpful fallback to catch non-POST deliveries (Stripe mistakenly configured or manual checks).
// Only accept non-POST methods on the fallback route so real webhooks (POST) are handled
// by the dedicated controller above. The fallback will help diagnose misconfigured
// webhook endpoints when Stripe uses the wrong HTTP method.
Route::match(['get', 'head', 'put', 'patch', 'delete', 'options'], '/webhooks/stripe', function (Request $request) {
    Log::warning('Webhook endpoint received non-POST request', ['method' => $request->method(), 'ip' => $request->ip()]);

    return response('Method not allowed. Stripe webhooks must POST to this endpoint.', 405);
});

// Payment success callback - verify Stripe session and activate post if possible
Route::get('/posts/payment/success', [PostPaymentController::class, 'success'])
    ->name('posts.payment.success');
