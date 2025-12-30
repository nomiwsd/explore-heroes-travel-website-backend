<?php

/**
 * ========================================
 * BACKEND API ONLY - NO WEB ROUTES
 * ========================================
 *
 * This file is intentionally minimal for API-only backend.
 * All functionality is handled through API routes in routes/api.php
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Disabled for API-only Backend)
|--------------------------------------------------------------------------
|
| This Laravel backend is configured as API-only.
| All routes are available through the API endpoints.
|
| Frontend: Use Next.js or any other frontend framework
| API Base: /api/*
|
*/

// Health check endpoint
Route::get('/', function () {
    return response()->json([
        'message' => 'Booking Core API - Backend Only',
        'version' => '3.0.0',
        'status' => 'active',
        'documentation' => 'See API endpoints in routes/api.php',
        'api_base' => url('/api'),
        'endpoints' => [
            'configs' => '/api/configs',
            'auth' => '/api/auth/*',
            'services' => '/api/services',
            'bookings' => '/api/booking/*',
        ]
    ], 200);
});

// API documentation redirect
Route::get('/docs', function () {
    return response()->json([
        'message' => 'API Documentation',
        'available_services' => [
            'tour' => 'Tour booking services',
            'hotel' => 'Hotel booking services',
            'flight' => 'Flight booking services',
            'car' => 'Car rental services',
            'boat' => 'Boat rental services',
            'event' => 'Event booking services',
            'space' => 'Space rental services'
        ],
        'auth_endpoints' => [
            'POST /api/auth/login' => 'User login',
            'POST /api/auth/register' => 'User registration',
            'POST /api/auth/logout' => 'User logout',
            'GET /api/auth/me' => 'Get current user'
        ],
        'note' => 'Use /api prefix for all endpoints'
    ], 200);
});

// Fallback for undefined routes
Route::fallback(function () {
    return response()->json([
        'error' => 'Route not found',
        'message' => 'This is an API-only backend. Please use /api/* endpoints.',
        'suggestion' => 'Visit / for available endpoints'
    ], 404);
});
