<?php

use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\CustomerRegistrationController;

use App\Http\Controllers\JobsListingController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

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

// Legal pages
Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/about', 'about')->name('about');
