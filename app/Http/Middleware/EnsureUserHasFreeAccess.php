<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class EnsureUserHasFreeAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Filament::auth()?->user() ?? $request->user();

        if (! $user || ! $user->is_free) {
            abort(403);
        }

        // Flash the session marker so it only lasts for the immediate redirect / next request.
        $request->session()->flash('customer_free_flow', true);

        return $next($request);
    }
}
