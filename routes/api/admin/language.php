<?php

/**
 * ADMIN LANGUAGE MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Language\Models\Language;

// =====================================================
// LANGUAGE MANAGEMENT
// =====================================================
Route::prefix('module/language')->middleware('auth:sanctum')->group(function () {
    // Get all languages
    Route::get('/', function (Request $request) {
        try {
            $query = Language::query();
            
            if ($request->has('s') && $request->s) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->s . '%')
                      ->orWhere('locale', 'LIKE', '%' . $request->s . '%');
                });
            }
            
            $languages = $query->orderBy('id', 'desc')->get();
            
            return response()->json([
                'data' => $languages->map(function ($lang) {
                    return [
                        'id' => $lang->id,
                        'name' => $lang->name,
                        'locale' => $lang->locale,
                        'flag' => $lang->flag,
                        'status' => $lang->status,
                        'is_default' => $lang->is_default,
                        'is_rtl' => $lang->is_rtl,
                    ];
                }),
                'total' => $languages->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single language
    Route::get('/edit/{id}', function ($id) {
        try {
            $language = Language::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $language->id,
                    'name' => $language->name,
                    'locale' => $language->locale,
                    'flag' => $language->flag,
                    'status' => $language->status,
                    'is_default' => $language->is_default,
                    'is_rtl' => $language->is_rtl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store/Update language
    Route::post('/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $language = Language::findOrFail($id);
            } else {
                $language = new Language();
            }
            
            $language->name = $request->input('name');
            $language->locale = $request->input('locale');
            $language->flag = $request->input('flag');
            $language->status = $request->input('status', 'publish');
            $language->is_default = $request->input('is_default', false);
            $language->is_rtl = $request->input('is_rtl', false);
            
            // If setting as default, unset other defaults
            if ($language->is_default) {
                Language::where('id', '!=', $language->id)->update(['is_default' => false]);
            }
            
            $language->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $language->id],
                'message' => 'Language saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete language
    Route::delete('/{id}', function ($id) {
        try {
            $language = Language::findOrFail($id);
            
            if ($language->is_default) {
                return response()->json(['error' => 'Cannot delete default language'], 400);
            }
            
            $language->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Language deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Set as default
    Route::post('/setDefault/{id}', function ($id) {
        try {
            Language::where('is_default', true)->update(['is_default' => false]);
            
            $language = Language::findOrFail($id);
            $language->is_default = true;
            $language->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Default language updated',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            // Don't allow bulk delete of default language
            $defaultLang = Language::where('is_default', true)->first();
            if ($action === 'delete' && $defaultLang && in_array($defaultLang->id, $ids)) {
                return response()->json(['error' => 'Cannot delete default language'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    Language::whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    Language::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    Language::whereIn('id', $ids)->update(['status' => 'draft']);
                    break;
                default:
                    return response()->json(['error' => 'Invalid action'], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($action) . ' completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// TRANSLATION MANAGEMENT
// =====================================================
Route::prefix('module/language/translations')->middleware('auth:sanctum')->group(function () {
    // Get all translations for a language
    Route::get('/{locale}', function ($locale, Request $request) {
        try {
            $group = $request->input('group', 'general');
            
            // Try to load from database first
            $translations = \DB::table('bc_translations')
                ->where('locale', $locale)
                ->where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
            
            // If empty, try to load from JSON file
            if (empty($translations)) {
                $filePath = lang_path($locale . '.json');
                if (file_exists($filePath)) {
                    $translations = json_decode(file_get_contents($filePath), true) ?? [];
                }
            }
            
            return response()->json([
                'locale' => $locale,
                'group' => $group,
                'translations' => $translations,
            ]);
        } catch (\Exception $e) {
            return response()->json(['translations' => []]);
        }
    });
    
    // Update translations
    Route::post('/{locale}', function ($locale, Request $request) {
        try {
            $translations = $request->input('translations', []);
            $group = $request->input('group', 'general');
            
            // Check if translations table exists
            if (!\Schema::hasTable('bc_translations')) {
                \Schema::create('bc_translations', function ($table) {
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
                \DB::table('bc_translations')->updateOrInsert(
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
});
