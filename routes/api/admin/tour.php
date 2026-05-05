<?php

/**
 * ADMIN TOUR MODULE ROUTES
 * All tour-related admin functionality
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;
use Modules\Core\Models\Terms;

Route::prefix('module/tour')->middleware('auth:sanctum')->group(function () {
    
    // =====================================================
    // STATIC ROUTES (Must be before wildcards)
    // =====================================================

    // Get tour experts
    Route::get('/experts', function () {
        try {
            $users = DB::table('users')
                ->whereNull('deleted_at')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
            
            return response()->json(['data' => $users]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Helper: ensure 'travel-styles' attribute exists, return its id
    $resolveTravelStylesAttrId = function () {
        $row = DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'travel-styles')
            ->first();
        if ($row) return $row->id;
        return DB::table('bc_attrs')->insertGetId([
            'name' => 'Travel Styles',
            'slug' => 'travel-styles',
            'service' => 'tour',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    // Get tour themes (admin: ?lang= for translated names, ?s= search)
    Route::get('/themes', function (Request $request) use ($resolveTravelStylesAttrId) {
        try {
            $attrId = $resolveTravelStylesAttrId();
            $lang = $request->query('lang');

            $query = Terms::query()->where('attr_id', $attrId);
            if ($s = $request->query('s')) {
                $query->where('name', 'LIKE', '%' . $s . '%');
            }
            $themes = $query->orderBy('name')->get();

            $transformed = $themes->map(function ($theme) use ($lang) {
                $translation = ($lang && !is_default_lang($lang)) ? $theme->translate($lang) : null;
                return [
                    'id' => $theme->id,
                    'name' => $translation->name ?? $theme->name,
                    'slug' => $theme->slug,
                    'icon' => $theme->icon,
                    'image_id' => $theme->image_id,
                    'image_url' => $theme->image_id ? get_file_url($theme->image_id, 'full') : null,
                    'attr_id' => $theme->attr_id,
                    'created_at' => $theme->created_at,
                ];
            });

            return response()->json([
                'data' => $transformed,
                'attr_id' => $attrId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Get single theme for editing (?lang= for translation row)
    Route::get('/themes/edit/{id}', function (Request $request, $id) {
        try {
            $theme = Terms::findOrFail($id);
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $theme->translate($lang);
            }

            return response()->json([
                'data' => [
                    'id' => $theme->id,
                    'name' => $translation->name ?? $theme->name,
                    'slug' => $theme->slug,
                    'icon' => $theme->icon,
                    'image_id' => $theme->image_id,
                    'image_url' => $theme->image_id ? get_file_url($theme->image_id, 'full') : null,
                    'attr_id' => $theme->attr_id,
                ],
                'translation' => $translation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Store/Update tour theme (?lang= for translation save)
    Route::post('/themes/store/{id?}', function (Request $request, $id = null) use ($resolveTravelStylesAttrId) {
        try {
            $lang = $request->query('lang') ?: $request->input('lang');
            $isTranslation = $lang && !is_default_lang($lang);

            $attrId = $resolveTravelStylesAttrId();

            if ($id) {
                $theme = Terms::findOrFail($id);
            } else {
                $theme = new Terms();
                $theme->attr_id = $attrId;
            }

            if (!$isTranslation) {
                $theme->name = $request->input('name');
                $theme->slug = $request->input('slug') ?: Str::slug($request->input('name'));
                $theme->icon = $request->input('icon');
                $theme->image_id = $request->input('image_id');
                $theme->attr_id = $attrId;
                $theme->save();
                $theme->saveTranslation($lang ?: get_main_lang(), false);
                $message = 'Theme saved successfully';
            } else {
                if (!$theme->id) {
                    return response()->json(['error' => 'Cannot create translation for non-existing theme'], 400);
                }
                $theme->saveTranslation($lang, false);
                $message = 'Theme translation saved successfully';
            }

            return response()->json([
                'success' => true,
                'data' => ['id' => $theme->id],
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Delete tour theme
    Route::delete('/themes/{id}', function ($id) {
        try {
            $theme = Terms::findOrFail($id);
            $theme->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Bulk Edit Tours
    Route::middleware('permission:tour_update')->post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    Tour::whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    Tour::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    Tour::whereIn('id', $ids)->update(['status' => 'draft']);
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

    // Get tour categories (admin: ?lang=, ?status= [all|publish|draft], ?s=)
    Route::get('/category', function (Request $request) {
        try {
            $query = TourCategory::query();

            $status = $request->query('status');
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($s = $request->query('s')) {
                $query->where('name', 'LIKE', '%' . $s . '%');
            }

            $rows = $query->orderBy('name')->get(['id', 'name', 'slug', 'status', 'parent_id']);

            $lang = $request->query('lang');
            $categories = $rows->map(function ($cat) use ($lang) {
                $translation = ($lang && !is_default_lang($lang)) ? $cat->translate($lang) : null;
                return [
                    'id' => $cat->id,
                    'name' => $translation->name ?? $cat->name,
                    'slug' => $cat->slug,
                    'status' => $cat->status,
                    'parent_id' => $cat->parent_id,
                ];
            });

            return response()->json(['data' => $categories]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    });

    // Get single tour category for editing (?lang= for translation)
    Route::get('/category/edit/{id}', function (Request $request, $id) {
        try {
            $category = TourCategory::findOrFail($id);

            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $category->translate($lang);
            }

            return response()->json([
                'data' => [
                    'id' => $category->id,
                    'name' => $translation->name ?? $category->name,
                    'slug' => $category->slug,
                    'status' => $category->status,
                    'parent_id' => $category->parent_id,
                ],
                'translation' => $translation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Create/Update category (?lang= for translation save)
    Route::post('/category/store/{id?}', function (Request $request, $id = null) {
        try {
            $lang = $request->query('lang') ?: $request->input('lang');
            $isTranslation = $lang && !is_default_lang($lang);

            if ($id) {
                $category = TourCategory::findOrFail($id);
            } else {
                $category = new TourCategory();
            }

            if (!$isTranslation) {
                $category->fill($request->only(['name', 'slug', 'status', 'parent_id']));
                $category->save();
                // Mirror to default-locale translation row
                $category->saveTranslation($lang ?: get_main_lang(), false);
                $message = 'Category saved successfully';
            } else {
                if (!$category->id) {
                    return response()->json(['error' => 'Cannot create translation for non-existing category'], 400);
                }
                $category->saveTranslation($lang, false);
                $message = 'Category translation saved successfully';
            }

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Delete category
    Route::delete('/category/{id}', function ($id) {
        try {
            $category = TourCategory::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // =====================================================
    // CORE TOUR CRUD (Wildcard routes follow)
    // =====================================================

    // Get all tours
    Route::get('/', function (Request $request) {
        try {
            $query = Tour::query();
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where('title', 'LIKE', '%' . $request->s . '%');
            }
            
            // Status filter
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            // Category filter
            if ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }
            
            // Location filter
            if ($request->has('location_id') && $request->location_id) {
                $query->where('location_id', $request->location_id);
            }
            
            // Featured filter
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->is_featured);
            }
            
            $perPage = $request->per_page ?? 20;
            $tours = $query->orderBy('id', 'desc')->paginate($perPage);
            
            // Transform data
            $data = $tours->map(function ($tour) {
                return [
                    'id' => $tour->id,
                    'title' => $tour->title,
                    'slug' => $tour->slug,
                    'image_id' => $tour->image_id,
                    'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                    'price' => $tour->price,
                    'sale_price' => $tour->sale_price,
                    'duration' => $tour->duration,
                    'nights' => $tour->nights,
                    'tour_type' => $tour->tour_type,
                    'hero_slider_count' => is_array($tour->hero_slider) ? count($tour->hero_slider) : 0,
                    'itinerary_count' => is_array($tour->itinerary) ? count($tour->itinerary) : 0,
                    'status' => $tour->status,
                    'is_featured' => $tour->is_featured,
                    'category_id' => $tour->category_id,
                    'category_name' => $tour->category_tour ? $tour->category_tour->name : null,
                    'location_id' => $tour->location_id,
                    'location_name' => $tour->location ? $tour->location->name : null,
                    'created_at' => $tour->created_at,
                    'updated_at' => $tour->updated_at,
                ];
            });
            
            return response()->json([
                'data' => $data,
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('permission:tour_view');

    // Get single tour for editing
    Route::get('/edit/{id}', function (Request $request, $id) {
        try {
            $tour = Tour::with(['location', 'category_tour', 'tourExpert'])->findOrFail($id);

            // Get translation if lang param is provided
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $tour->translate($lang);
            }

            $toArray = fn($value) => is_array($value) ? $value : (
                is_string($value) && !empty($value) ? json_decode($value, true) ?? [] : []
            );
            
            $inclusions = $toArray($tour->inclusions);
            if (empty($inclusions)) $inclusions = $toArray($tour->include);
            
            $exclusions = $toArray($tour->exclusions);
            if (empty($exclusions)) $exclusions = $toArray($tour->exclude);
            
            $highlights = $toArray($tour->highlights);
            if (empty($highlights)) $highlights = $toArray($tour->highlight);
            
            $tourExpert = null;
            if ($tour->tour_expert_id && $tour->tourExpert) {
                $tourExpert = [
                    'id' => (int) $tour->tourExpert->id,
                    'name' => $tour->tourExpert->getDisplayName() ?? $tour->tourExpert->name ?? $tour->tourExpert->first_name,
                    'email' => $tour->tourExpert->email ?? '',
                    'avatar' => $tour->tourExpert->avatar_id ? get_file_url($tour->tourExpert->avatar_id, 'thumb') : null,
                ];
            }

            $responseData = [
                'data' => [
                    'id' => (int) $tour->id,
                    'title' => $tour->title ?? '',
                    'slug' => $tour->slug ?? '',
                    'short_desc' => $tour->short_desc ?? '',
                    'status' => $tour->status ?? 'draft',
                    'is_featured' => (int) ($tour->is_featured ?? 0),
                    'category_ids' => $toArray($tour->category_ids),
                    'location_id' => $tour->location_id ? (int) $tour->location_id : null,
                    'location_name' => $tour->location ? $tour->location->name : null,
                    'price' => (float) ($tour->price ?? 0),
                    'sale_price' => (float) ($tour->sale_price ?? 0),
                    'pricing_type' => $tour->pricing_type ?? 'per_person',
                    'group_price' => (float) ($tour->group_price ?? 0),
                    'child_price' => (float) ($tour->child_price ?? 0),
                    'duration' => (int) ($tour->duration ?? 1),
                    'duration_type' => $tour->duration_type ?? 'days',
                    'nights' => (int) ($tour->nights ?? 0),
                    'tour_type' => $tour->tour_type ?? '',
                    'min_people' => (int) ($tour->min_people ?? 1),
                    'max_people' => (int) ($tour->max_people ?? 10),
                    'suitable_for' => $toArray($tour->suitable_for),
                    'tour_themes' => $toArray($tour->tour_themes),
                    'cities_covered' => $toArray($tour->cities_covered),
                    'tour_expert_id' => $tour->tour_expert_id ? (int) $tour->tour_expert_id : null,
                    'tour_expert' => $tourExpert,
                    'image_id' => $tour->image_id ? (int) $tour->image_id : null,
                    'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                    'banner_image_id' => $tour->banner_image_id ? (int) $tour->banner_image_id : null,
                    'banner_image_url' => $tour->banner_image_id ? get_file_url($tour->banner_image_id, 'full') : ($tour->banner_image ? url($tour->banner_image) : null),
                    'hero_slider' => $toArray($tour->hero_slider),
                    'address' => $tour->address ?? '',
                    'map_lat' => $tour->map_lat ?? '',
                    'map_lng' => $tour->map_lng ?? '',
                    'map_zoom' => (int) ($tour->map_zoom ?? 10),
                    'map_image_id' => $tour->map_image_id ? (int) $tour->map_image_id : null,
                    'map_image_url' => $tour->map_image_id ? get_file_url($tour->map_image_id, 'full') : null,
                    'map_embed' => $tour->map_embed ?? '',
                    'inclusions' => $inclusions,
                    'exclusions' => $exclusions,
                    'highlights' => $highlights,
                    'itinerary' => $toArray($tour->itinerary),
                    'faqs' => $toArray($tour->faqs),
                    'conditions' => $tour->conditions ?? '',
                    'cancellation_policy' => $tour->cancellation_policy ?? '',
                    'child_policy' => $tour->child_policy ?? '',
                    'payment_terms' => $tour->payment_terms ?? '',
                    'min_day_before_booking' => (int) ($tour->min_day_before_booking ?? 0),
                    'enable_fixed_date' => (int) ($tour->enable_fixed_date ?? 0),
                    'start_date' => $tour->start_date,
                    'end_date' => $tour->end_date,
                    'last_booking_date' => $tour->last_booking_date,
                    'availability_dates' => $toArray($tour->availability_dates),
                    'related_tour_ids' => $toArray($tour->related_tour_ids),
                    'seo_title' => $tour->seo_title ?? '',
                    'seo_desc' => $tour->seo_desc ?? '',
                    'seo_keywords' => $tour->seo_keywords ?? '',
                    'canonical_url' => $tour->canonical_url ?? '',
                    'robots_meta' => $tour->robots_meta ?? '',
                    'schema_markup' => $tour->schema_markup ?? '',             
                    'og_title' => $tour->og_title ?? '',
                    'og_description' => $tour->og_description ?? '',
                    'og_image_id' => $tour->og_image_id ? (int)$tour->og_image_id : null,
                    'og_image_url' => $tour->og_image_url ?? '',
                    'twitter_card' => $tour->twitter_card ?? 'summary_large_image',
                    'twitter_title' => $tour->twitter_title ?? '',
                    'twitter_description' => $tour->twitter_description ?? '',
                    'twitter_image_id' => $tour->twitter_image_id ? (int)$tour->twitter_image_id : null,
                    'twitter_image_url' => $tour->twitter_image_url ?? '',
                    'created_at' => $tour->created_at,
                    'updated_at' => $tour->updated_at,
                    'author' => $tour->author ? [
                        'id' => (int) $tour->author->id,
                        'name' => $tour->author->getDisplayName() ?? $tour->author->name ?? $tour->author->first_name,
                    ] : null,
                ],
            ];

            // Include translation if fetched
            if ($translation) {
                $responseData['translation'] = $translation;
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('permission:tour_view');
    
    // Create/Update tour (protected)
    Route::middleware('permission:tour_update')->post('/store/{id?}', function (Request $request, $id = null) {
        try {
            $lang = $request->query('lang') ?: $request->input('lang');
            $isTranslation = $lang && !is_default_lang($lang);

            if ($id) {
                $tour = Tour::findOrFail($id);
            } else {
                $tour = new Tour();
            }
            
            $allowedFields = [
                'title', 'slug', 'short_desc', 'status', 'is_featured',
                'category_ids', 'location_id',
                'price', 'sale_price', 'pricing_type', 'group_price', 'child_price',
                'duration', 'duration_type', 'nights', 'tour_type', 'min_people', 'max_people',
                'suitable_for', 'tour_themes', 'cities_covered', 'tour_expert_id',
                'image_id', 'banner_image_id', 'banner_image_url', 'hero_slider',
                'address', 'map_lat', 'map_lng', 'map_zoom', 'map_image_id', 'map_embed',
                'inclusions', 'exclusions', 'highlights', 'itinerary', 'faqs',
                'conditions', 'cancellation_policy', 'child_policy', 'payment_terms',
                'min_day_before_booking', 'enable_fixed_date', 'start_date', 'end_date', 'last_booking_date',
                'availability_dates', 'related_tour_ids',
                'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'canonical_url', 'robots_meta', 'schema_markup',
                'og_title', 'og_description', 'og_image', 'og_image_id', 'og_image_url',
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image', 'twitter_image_id', 'twitter_image_url',
            ];
            
            $tour->fill($request->only($allowedFields));
            
            if (!$id) {
                $tour->create_user = $request->user()->id ?? 1;
            }

            // Use saveOriginOrTranslation for proper translation handling
            if ($isTranslation) {
                // For translations, don't save the main tour, only save translation
                $tour->saveTranslation($lang, true);
                $message = 'Translation saved successfully';
            } else {
                // For default language, save both main tour and translation
                $tour->save();
                $tour->saveTranslation($lang ?: 'en', true);
                $message = $id ? 'Tour updated successfully' : 'Tour created successfully';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['id' => (int) $tour->id],
            ]);
        } catch (\Exception $e) {
            Log::error('Tour store error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Delete tour (protected)
    Route::middleware('permission:tour_delete')->delete('/{id}', function ($id) {
        try {
            $tour = Tour::findOrFail($id);
            $tour->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tour deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

});