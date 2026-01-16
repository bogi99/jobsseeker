<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class EnsureAdminPanelAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Prefer the application auth user (works with actingAs in tests), fall back to Filament auth.
        $user = \Illuminate\Support\Facades\Auth::user() ?? Filament::auth()?->user();

        // If there's no authenticated user yet (e.g. visiting the login page), allow the
        // request to continue — the Filament Authenticate middleware will handle the login.
        if (! $user) {
            return $next($request);
        }

        // Reload relations to ensure we have the current usertype available in middleware
        $user = $user->fresh();

        // Only users with an admin or superadmin usertype may access the admin panel.
        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
