<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PinAuthenticated
{
    /**
     * Handle an incoming request.
     * Redirect to PIN login if not authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
