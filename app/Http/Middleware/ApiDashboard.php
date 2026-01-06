<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\User;

class ApiDashboard
{
    /**
     * Handle an incoming request.
     * Supports both session-based and Bearer token authentication.
     */
    public function handle($request, Closure $next, $guard = null)
    {
        // Check if already authenticated via session
        if (!Auth::check()) {
            // Try Bearer token authentication for API requests
            $token = $request->bearerToken();
            if ($token) {
                $user = $this->authenticateWithToken($token);
                if ($user) {
                    Auth::login($user);
                }
            }
            
            // If still not authenticated, redirect to login
            if (!Auth::check()) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                return redirect(route('login', ['redirect' => $request->getRequestUri()]));
            }
        }
        
        // Check dashboard access permission
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission') && !$user->hasPermission('dashboard_access')) {
            if ($this->isApiRequest($request)) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            return redirect('/');
        }
        
        return $next($request);
    }
    
    /**
     * Authenticate user with Sanctum token
     */
    protected function authenticateWithToken($token)
    {
        try {
            // Sanctum tokens format: {id}|{token}
            // We need to extract the token part and hash it
            if (str_contains($token, '|')) {
                [$tokenId, $plainToken] = explode('|', $token, 2);
                $tokenHash = hash('sha256', $plainToken);
                
                $tokenModel = \DB::table('personal_access_tokens')
                    ->where('id', $tokenId)
                    ->where('token', $tokenHash)
                    ->first();
                    
                if ($tokenModel) {
                    $user = User::find($tokenModel->tokenable_id);
                    if ($user) {
                        // Update last used timestamp
                        \DB::table('personal_access_tokens')
                            ->where('id', $tokenModel->id)
                            ->update(['last_used_at' => now()]);
                        return $user;
                    }
                }
            } else {
                // Try direct hash lookup (for tokens without ID prefix)
                $tokenHash = hash('sha256', $token);
                $tokenModel = \DB::table('personal_access_tokens')
                    ->where('token', $tokenHash)
                    ->first();
                    
                if ($tokenModel) {
                    return User::find($tokenModel->tokenable_id);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Token auth failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Check if the request expects JSON response
     */
    protected function isApiRequest($request): bool
    {
        return $request->wantsJson() 
            || $request->expectsJson() 
            || $request->is('api/*') 
            || $request->is('module/*')
            || $request->is('media/*')
            || $request->ajax()
            || $request->header('Accept') === 'application/json';
    }
}
