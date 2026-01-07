<?php

/**
 * ADMIN AUTHENTICATION ROUTES
 * Login, Logout, User Profile for Admin Dashboard
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\DashboardController;

// =====================================================
// ADMIN AUTHENTICATION
// =====================================================
Route::prefix('admin')->group(function () {
    // Login
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url ?? null,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    });

    // Logout
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    })->middleware('auth:sanctum');

    // Get authenticated user
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    })->middleware('auth:sanctum');

    // Dashboard Routes (protected)
    Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/submissions', [DashboardController::class, 'getLatestSubmissions']);
        Route::get('/activity', [DashboardController::class, 'getActivityLog']);
        Route::get('/website-status', [DashboardController::class, 'getWebsiteStatus']);
        Route::post('/submissions/{id}/status', [DashboardController::class, 'updateSubmissionStatus']);
    });
});

// Get authenticated user (alternative route)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
