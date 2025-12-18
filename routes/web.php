<?php

use App\Http\Controllers\JobsListingController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Jobs
Route::get('/jobs', [JobsListingController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{post}', [JobsListingController::class, 'show'])->name('jobs.show');

// Legal pages
Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/about', 'about')->name('about');
