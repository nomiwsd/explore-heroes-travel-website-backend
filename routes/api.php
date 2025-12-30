<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Test Routes - For Database Testing
Route::prefix('test')->group(function () {
    Route::get('/health', [TestController::class, 'health']);
    Route::get('/database', [TestController::class, 'testDatabase']);
    Route::get('/table/{tableName}', [TestController::class, 'getTableData']);
    Route::get('/users', [TestController::class, 'getUsers']);
    Route::get('/bookings', [TestController::class, 'getBookings']);
});

// Admin Authentication Routes
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
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
