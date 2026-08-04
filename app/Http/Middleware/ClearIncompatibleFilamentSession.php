<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * If an authenticated user cannot use the current Filament panel, log them out
 * and send them to that panel's login instead of a bare 403.
 */
class ClearIncompatibleFilamentSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return $next($request);
        }

        $guard = Auth::guard($panel->getAuthGuard());
        $user = $guard->user();

        if (! $user) {
            return $next($request);
        }

        $allowed = $user instanceof FilamentUser
            ? $user->canAccessPanel($panel)
            : app()->environment('local');

        if ($allowed) {
            return $next($request);
        }

        $guard->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->guest($panel->getLoginUrl() ?? '/login')
            ->with('status', 'You were signed out because that account cannot access this portal. Please sign in with the correct account.');
    }
}
