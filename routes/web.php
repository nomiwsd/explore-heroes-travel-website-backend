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

// Serve static files from uploads directory
// This is needed because the fallback route would otherwise catch these requests
// Files can be in public/uploads OR storage/app/public/uploads depending on config
Route::get('/uploads/{path}', function ($path) {
    // First try public/uploads (as per 'uploads' disk config)
    $filePath = public_path('uploads/' . $path);
    
    // If not found, try storage/app/public/uploads (where files are actually being saved)
    if (!file_exists($filePath)) {
        $filePath = storage_path('app/public/uploads/' . $path);
    }
    
    // If still not found, return 404
    if (!file_exists($filePath)) {
        return response()->json(['error' => 'File not found', 'looked_in' => [
            public_path('uploads/' . $path),
            storage_path('app/public/uploads/' . $path)
        ]], 404);
    }
    
    // Get mime type
    $mimeType = mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');

// Fallback for undefined routes
Route::fallback(function () {
    return response()->json([
        'error' => 'Route not found',
        'message' => 'This is an API-only backend. Please use /api/* endpoints.',
        'suggestion' => 'Visit / for available endpoints'
    ], 404);
});

// Dynamic robots.txt
Route::get('/robots.txt', function () {
    $content = \Modules\Core\Models\Settings::item('robots_txt_content', "User-agent: *\nAllow: /\n\nSitemap: " . config('app.url') . "/sitemap.xml");
    return response($content)->header('Content-Type', 'text/plain');
});
