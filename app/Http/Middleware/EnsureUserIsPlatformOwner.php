<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPlatformOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is logged in and if they are a platform owner.
        // The isPlatformOwner() method is the one we created in the User model.
        if (! $request->user() || ! $request->user()->isPlatformOwner()) {
            // If not, block them with a "Forbidden" error.
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
