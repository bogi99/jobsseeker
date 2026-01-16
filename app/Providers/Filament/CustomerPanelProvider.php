<?php

namespace App\Providers\Filament;

use App\Filament\Customer\Pages\CreateFreePost as CustomerCreateFreePost;
use App\Filament\Customer\Pages\CreatePost as CustomerCreatePost;
use App\Filament\Customer\Pages\Dashboard as CustomerDashboard;
use App\Filament\Customer\Resources\PostResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('customer')
            ->login()
            // ->brandLogo(asset('images/logo.png'))
            // ->brandLogoHeight('55px')
            // ->brandName('JobRat Customer Panel')
            ->brandLogo(fn () => view('filament.customer.logo'))
            ->brandLogoHeight('55px') // adjust as needed
            ->colors([
                'primary' => Color::Blue,
            ])
            ->resources([
                PostResource::class,
            ])
            ->pages([
                CustomerDashboard::class,
                CustomerCreatePost::class,
                CustomerCreateFreePost::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
