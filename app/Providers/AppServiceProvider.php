<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Post observer for lifecycle handling of activation/pausing.
        \App\Models\Post::observe(\App\Observers\PostObserver::class);
    }
}
