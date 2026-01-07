<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Minimal RedirectToInstaller middleware.
 * Restores missing class to avoid container binding errors.
 * If installer flow is needed later, implement redirect logic here.
 */
class RedirectToInstaller
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Default behavior: just pass the request through.
        return $next($request);
    }
}
