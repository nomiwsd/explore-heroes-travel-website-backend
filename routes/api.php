<?php

/**
 * API ROUTES - MAIN FILE
 * 
 * This file loads all API route files organized by:
 * - Public routes (no authentication required)
 * - Auth routes (authentication endpoints)
 * - Admin routes (authenticated admin endpoints)
 * 
 * Laravel 12.x Structure Implementation
 * 
 * @author Explore Heroes
 * @version 2.0
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| These routes are accessible without authentication.
| Includes: menus, destinations, tours, settings, translations, 
| reviews, news/blog, pages, contact form submission
|
*/
require __DIR__ . '/api/public.php';

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for admin authentication including login, logout, 
| current user info, and dashboard data.
|
*/
require __DIR__ . '/api/auth.php';

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Protected routes for the admin dashboard.
| All routes require authentication via Sanctum.
|
*/

// Tour Management
require __DIR__ . '/api/admin/tour.php';

// Location/Destination Management (Handled by Modules/Location)
Route::prefix('module/location')->group(function () {
    Route::get('/', [\Modules\Location\Admin\LocationController::class, 'index']);
    Route::get('/edit/{id}', [\Modules\Location\Admin\LocationController::class, 'edit']);
    Route::post('/store', [\Modules\Location\Admin\LocationController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/store/{id}', [\Modules\Location\Admin\LocationController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/bulkEdit', [\Modules\Location\Admin\LocationController::class, 'bulkEdit'])->middleware('auth:sanctum');
});

// News/Blog Management
require __DIR__ . '/api/admin/news.php';

// Page Management
require __DIR__ . '/api/admin/page.php';

// Review Management
require __DIR__ . '/api/admin/review.php';

// Contact Submissions Management
require __DIR__ . '/api/admin/contact.php';

// Media Library Management
require __DIR__ . '/api/admin/media.php';

// Language & Translation Management
require __DIR__ . '/api/admin/language.php';

// User Management
require __DIR__ . '/api/admin/user.php';

// Core Module (Menu, Settings, SEO)
require __DIR__ . '/api/admin/core.php';

// Reports & Analytics (Kept as requested)
require __DIR__ . '/api/admin/report.php';

// SMS Module (Kept as requested)
require __DIR__ . '/api/admin/sms.php';

/*
|--------------------------------------------------------------------------
| API Health Check
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '2.0',
    ]);
});

/*
|--------------------------------------------------------------------------
| API Info
|--------------------------------------------------------------------------
*/
Route::get('/info', function () {
    return response()->json([
        'name' => 'Explore Heroes Travel API',
        'version' => '2.0',
        'description' => 'Backend API for Explore Heroes Travel Website',
        'documentation' => '/api/docs',
        'modules' => [
            'tour' => 'Tour Management',
            'location' => 'Destination Management',
            'news' => 'Blog/News Management',
            'page' => 'Page Management',
            'review' => 'Review Management',
            'contact' => 'Contact Form Management',
            'media' => 'Media Library',
            'language' => 'Multi-language Support',
            'user' => 'User Management',
            'core' => 'Menu, Settings, SEO',
            'report' => 'Reports & Analytics',
            'sms' => 'SMS Notifications',
        ],
    ]);
});
