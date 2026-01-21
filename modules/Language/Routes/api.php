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

    /**
     * Remote Scan Endpoint (Publicly accessible to receive keys from frontend script)
     */
    Route::post('/scan', function (Request $request) {
        // 1. Import External Keys (e.g. sent from Frontend via script)
        $importedExternal = 0;

        if ($request->has('keys') && is_array($request->input('keys'))) {
            $externalKeys = $request->input('keys');

            // Add the translations to the database
            $all_string = \Modules\Language\Models\Translation::select("string", "id")->where("locale", "raw")->get()->pluck('id', 'string')->toArray();

            foreach ($externalKeys as $key) {
                if (!$key || is_numeric($key)) continue;

                $defaultText = $key;
                // Handle object format {key: '...', default: '...'} if needed
                if (is_array($key)) {
                    $defaultText = $key['default'] ?? $key['key'];
                    $key = $key['key'];
                }

                if (empty($all_string[$key])) {
                    $raw = new \Modules\Language\Models\Translation([
                        'locale' => 'raw',
                        'string' => $key
                    ]);
                    $raw->save();
                    $parentId = $raw->id;
                    $importedExternal++;
                } else {
                    $parentId = $all_string[$key];
                }

                // Auto-fill English (en) if it doesn't exist
                $checkEn = \Modules\Language\Models\Translation::where('locale', 'en')->where('parent_id', $parentId)->first();
                if (!$checkEn) {
                    $en = new \Modules\Language\Models\Translation([
                        'locale' => 'en',
                        'string' => $defaultText,
                        'parent_id' => $parentId
                    ]);
                    $en->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $importedExternal,
            'message' => "Imported {$importedExternal} remote strings"
        ]);
    });
});
