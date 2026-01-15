<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\AuditLog;
use Illuminate\Support\Str;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $path = $request->path();
            
            // Skip logging for audit logs retrieval itself to prevent noise/loops
            // Also skip common read-only heavy paths if needed, but user asked for "each and every detail"
            if (!Str::contains($path, 'audit-logs')) {
                
                $method = $request->method();
                $event = $method . ' ' . $path;
                
                // For meaningful events, we can map common paths to friendlier names
                // but for "everything", raw info is best.
                
                // Exclude sensitive data from logging
                $input = $request->except(['password', 'password_confirmation', 'token']);
                
                try {
                    AuditLog::create([
                        'user_id' => $user->id,
                        'event' => $event,
                        'auditable_type' => 'Route', // Generic type for page visits
                        'auditable_id' => 0, // No specific model ID usually
                        'url' => $request->fullUrl(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'new_values' => !empty($input) && $method !== 'GET' ? json_encode($input) : null,
                    ]);
                } catch (\Exception $e) {
                    // Do not fail the request if logging fails
                    // \Log::error('Audit Log Error: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }
}
