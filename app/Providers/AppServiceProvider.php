<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Post;
use App\Observers\PostObserver;
use App\View\Composers\MetaKeywordsComposer;

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
        Post::observe(PostObserver::class);

        // Bind the meta keywords composer to relevant views so it can compute dynamic keywords
        View::composer([
            'welcome',
            'jobs.*',
            'jobs.show',
            'about',
            'terms',
            'privacy',
        ], MetaKeywordsComposer::class);
    }
}
