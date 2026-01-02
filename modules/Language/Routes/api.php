<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Language\Models\Language;

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

// Public API for translations (no auth required)
Route::prefix('translations')->group(function () {
    // Get all active languages
    Route::get('/languages', function () {
        $languages = Language::where('status', 'publish')
            ->orderByRaw('CASE WHEN is_default = 1 THEN 0 ELSE 1 END')
            ->get(['id', 'locale', 'name', 'flag', 'is_default', 'status']);

        return response()->json($languages);
    });

    // Get translations for a specific locale
    Route::get('/{locale}', function ($locale) {
        // First try to get from public file
        $publicFile = base_path('public/locales/' . $locale . '.json');
        if (file_exists($publicFile)) {
            $content = file_get_contents($publicFile);
            return response($content)->header('Content-Type', 'application/json');
        }

        // Fallback to resources/lang file
        $file = base_path('resources/lang/' . $locale . '.json');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            return response($content)->header('Content-Type', 'application/json');
        }

        // If no file exists, return empty object
        return response()->json([]);
    });
});
