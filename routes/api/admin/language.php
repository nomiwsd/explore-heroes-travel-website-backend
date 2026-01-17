<?php

/**
 * ADMIN LANGUAGE MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Modules\Language\Models\Language;

// =====================================================
// LANGUAGE MANAGEMENT
// =====================================================
Route::prefix('module/language')->middleware('auth:sanctum')->group(function () {
    // Get all languages & Create (Index handles both)
    Route::match(['get', 'post'], '/', [\Modules\Language\Admin\LanguageController::class, 'index']);

    // Get single language & Update (Edit handles both)
    Route::match(['get', 'post'], '/edit/{id}', [\Modules\Language\Admin\LanguageController::class, 'edit']);

    // Dedicated default setter (Post body style)
    Route::post('/setDefault', [\Modules\Language\Admin\LanguageController::class, 'setDefault']);

    // Bulk edit
    Route::post('/bulkEdit', [\Modules\Language\Admin\LanguageController::class, 'bulkEdit']);

    // Delete (mapped to bulkEdit or specific delete? Controller bulkEdit handles delete via action='delete')
    // But RESTful delete might expect DELETE method.
    // LanguageController doesn't have a 'delete' method, it uses bulkEdit mostly? 
    // Or users index logic?
    // Let's check bulkEdit logic. It handles delete.
    // Frontend uses: deleteLanguage(id) -> POST bulkEdit (action: delete) ?
    // Check language-service.ts
});

// =====================================================
// TRANSLATION MANAGEMENT
// =====================================================
Route::prefix('module/language/translations')->middleware('auth:sanctum')->group(function () {
    // Get all translations for a language (paginated with stats)
    Route::get('/{locale}', [\Modules\Language\Admin\TranslationsController::class, 'getTranslationsApi']);
    
    // Update translations
    Route::post('/{locale}', function ($locale, Request $request) {
        try {
            $translations = $request->input('translations', []);
            $group = $request->input('group', 'general');

            // Check if translations table exists
            if (!Schema::hasTable('bc_translations')) {
                Schema::create('bc_translations', function ($table) {
                    $table->id();
                    $table->string('locale', 10);
                    $table->string('group', 50)->default('general');
                    $table->string('key');
                    $table->text('value')->nullable();
                    $table->timestamps();
                    $table->unique(['locale', 'group', 'key']);
                });
            }
            
            foreach ($translations as $key => $value) {
                DB::table('bc_translations')->updateOrInsert(
                    ['locale' => $locale, 'group' => $group, 'key' => $key],
                    ['value' => $value, 'updated_at' => now()]
                );
            }
            
            // Also update JSON file
            $filePath = lang_path($locale . '.json');
            $existingTranslations = [];
            if (file_exists($filePath)) {
                $existingTranslations = json_decode(file_get_contents($filePath), true) ?? [];
            }
            
            $mergedTranslations = array_merge($existingTranslations, $translations);
            file_put_contents($filePath, json_encode($mergedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return response()->json([
                'success' => true,
                'message' => 'Translations updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get translation groups
    Route::get('/groups/list', function () {
        try {
            $groups = ['general', 'navigation', 'forms', 'validation', 'errors', 'tours', 'destinations', 'booking'];
            
            return response()->json($groups);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Import translations from file
    Route::post('/import/{locale}', function ($locale, Request $request) {
        try {
            $file = $request->file('file');
            
            if (!$file) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }
            
            $content = file_get_contents($file->getRealPath());
            $translations = json_decode($content, true);
            
            if (!$translations) {
                return response()->json(['error' => 'Invalid JSON file'], 400);
            }
            
            // Save to JSON file
            $filePath = lang_path($locale . '.json');
            file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return response()->json([
                'success' => true,
                'message' => 'Translations imported successfully',
                'count' => count($translations),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Export translations
    Route::get('/export/{locale}', function ($locale) {
        try {
            $filePath = lang_path($locale . '.json');
            
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Translation file not found'], 404);
            }
            
            $content = file_get_contents($filePath);
            
            return response($content)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $locale . '.json"');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Build translations (generate JSON files) - calls TranslationsController
    Route::post('/{locale}/build', [\Modules\Language\Admin\TranslationsController::class, 'buildTranslationsApi']);

    // Save translations
    Route::post('/{locale}/save', [\Modules\Language\Admin\TranslationsController::class, 'saveTranslationsApi']);

    // Get translation stats
    Route::get('/{locale}/stats', [\Modules\Language\Admin\TranslationsController::class, 'getStatsApi']);

    // Scan for new translatable strings
    Route::post('/scan', [\Modules\Language\Admin\TranslationsController::class, 'scanForStringsApi']);

    // Import translations from JSON file
    Route::post('/import', [\Modules\Language\Admin\TranslationsController::class, 'loadTranslateJson']);
});
