<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HideDebugbar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // For API-only backend, we disable the debugbar
        if (class_exists('\Debugbar')) {
            \Debugbar::disable();
        }
        
        return $next($request);
    }
}
