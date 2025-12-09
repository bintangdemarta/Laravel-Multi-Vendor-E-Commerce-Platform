<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate cart session ID for guests if not exists
        if (!$request->user() && !$request->session()->has('cart_session_id')) {
            $request->session()->put('cart_session_id', \Illuminate\Support\Str::uuid()->toString());
        }

        return $next($request);
    }
}
