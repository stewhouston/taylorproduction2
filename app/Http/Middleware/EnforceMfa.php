<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceMfa
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. If the user is authenticated but hasn't set up 2FA yet...
        if ($user && !$user->two_factor_secret) {
            
            // 2. Allow them to access the profile endpoints and Livewire/Inertia requests 
            // so they can actually enable it without getting stuck in a redirect loop.
            if ($request->routeIs('profile.show', 'two-factor.*', 'livewire.*') || $request->is('user/two-factor-authentication')) {
                return $next($request);
            }

            // 3. Otherwise, kick them back to the profile page to set up MFA.
            return redirect()->route('profile.show')
                ->with('danger', 'You must enable Two-Factor Authentication to access this app.');
        }

        return $next($request);
    }
}
