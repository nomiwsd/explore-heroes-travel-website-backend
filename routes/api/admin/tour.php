<?php

/**
 * ADMIN TOUR MODULE ROUTES
 * All tour-related admin functionality
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;

Route::prefix('module/tour')->group(function () {
    // Get all tours (for admin listing)
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
                    'nights' => $tour->nights, // Added
                    'tour_type' => $tour->tour_type, // Added
                    'hero_slider_count' => is_array($tour->hero_slider) ? count($tour->hero_slider) : 0, // Added to replace gallery_count
                    'itinerary_count' => is_array($tour->itinerary) ? count($tour->itinerary) : 0, // Added
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
    });
    
    // Get single tour for editing
    Route::get('/edit/{id}', function ($id) {
        try {
            $tour = Tour::with(['location', 'category_tour', 'tourExpert'])->findOrFail($id);
            
            // Helper to ensure array type
            $toArray = fn($value) => is_array($value) ? $value : (
                is_string($value) && !empty($value) ? json_decode($value, true) ?? [] : []
            );
            
            // Merge old fields into new format (inclusions takes priority over include)
            $inclusions = $toArray($tour->inclusions);
            if (empty($inclusions)) {
                $inclusions = $toArray($tour->include);
            }
            
            $exclusions = $toArray($tour->exclusions);
            if (empty($exclusions)) {
                $exclusions = $toArray($tour->exclude);
            }
            
            $highlights = $toArray($tour->highlights);
            if (empty($highlights)) {
                $highlights = $toArray($tour->highlight);
            }
            
            // Get tour expert data
            $tourExpert = null;
            if ($tour->tour_expert_id && $tour->tourExpert) {
                $tourExpert = [
                    'id' => (int) $tour->tourExpert->id,
                    'name' => $tour->tourExpert->getDisplayName() ?? $tour->tourExpert->name ?? $tour->tourExpert->first_name,
                    'email' => $tour->tourExpert->email ?? '',
                    'avatar' => $tour->tourExpert->avatar_id ? get_file_url($tour->tourExpert->avatar_id, 'thumb') : null,
                ];
            }
            
            return response()->json([
                'data' => [
                    // Basic Info
                    'id' => (int) $tour->id,
                    'title' => $tour->title ?? '',
                    'slug' => $tour->slug ?? '',
                    'short_desc' => $tour->short_desc ?? '',
                    'status' => $tour->status ?? 'draft',
                    'is_featured' => (int) ($tour->is_featured ?? 0),
                    
                    // Category & Location (ensure integers for Select components)
                    'category_ids' => $toArray($tour->category_ids),
                    'location_id' => $tour->location_id ? (int) $tour->location_id : null,
                    'location_name' => $tour->location ? $tour->location->name : null,
                    
                    // Pricing (ensure proper number types)
                    'price' => (float) ($tour->price ?? 0),
                    'sale_price' => (float) ($tour->sale_price ?? 0),
                    'pricing_type' => $tour->pricing_type ?? 'per_person',
                    'group_price' => (float) ($tour->group_price ?? 0),
                    'child_price' => (float) ($tour->child_price ?? 0),
                    
                    // Duration & Capacity
                    'duration' => (int) ($tour->duration ?? 1),
                    'duration_type' => $tour->duration_type ?? 'days',
                    'nights' => (int) ($tour->nights ?? 0),
                    'tour_type' => $tour->tour_type ?? '',
                    'min_people' => (int) ($tour->min_people ?? 1),
                    'max_people' => (int) ($tour->max_people ?? 10),
                    
                    // New Basic Info Arrays (ensure always arrays)
                    'suitable_for' => $toArray($tour->suitable_for),
                    'tour_themes' => $toArray($tour->tour_themes),
                    'cities_covered' => $toArray($tour->cities_covered),
               
                    'tour_expert_id' => $tour->tour_expert_id ? (int) $tour->tour_expert_id : null,
                    'tour_expert' => $tourExpert,
                    
                    // Images
                    'image_id' => $tour->image_id ? (int) $tour->image_id : null,
                    'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                    'banner_image_id' => $tour->banner_image_id ? (int) $tour->banner_image_id : null,
                    'banner_image_url' => $tour->banner_image_id ? get_file_url($tour->banner_image_id, 'full') : ($tour->banner_image ? url($tour->banner_image) : null),
                    
                    'hero_slider' => $toArray($tour->hero_slider),
                    
                    // Location Details
                    'address' => $tour->address ?? '',
                    'map_lat' => $tour->map_lat ?? '',
                    'map_lng' => $tour->map_lng ?? '',
                    'map_zoom' => (int) ($tour->map_zoom ?? 10),
                    'map_image_id' => $tour->map_image_id ? (int) $tour->map_image_id : null,
                    'map_image_url' => $tour->map_image_id ? get_file_url($tour->map_image_id, 'full') : null,
                    'map_embed' => $tour->map_embed ?? '',
                    
                    // Content Arrays (using merged values)
                    'inclusions' => $inclusions,
                    'exclusions' => $exclusions,
                    'highlights' => $highlights,
                    'itinerary' => $toArray($tour->itinerary),
                    'faqs' => $toArray($tour->faqs),
                    
                    // Policies
                    'conditions' => $tour->conditions ?? '',
                    'cancellation_policy' => $tour->cancellation_policy ?? '',
                    'child_policy' => $tour->child_policy ?? '',
                    'payment_terms' => $tour->payment_terms ?? '',
                    
                    // Availability
                    'min_day_before_booking' => (int) ($tour->min_day_before_booking ?? 0),
                    'enable_fixed_date' => (int) ($tour->enable_fixed_date ?? 0),
                    'start_date' => $tour->start_date,
                    'end_date' => $tour->end_date,
                    'last_booking_date' => $tour->last_booking_date,
                    'availability_dates' => $toArray($tour->availability_dates),
                    'related_tour_ids' => $toArray($tour->related_tour_ids),
                    
                    // SEO fields
                    'seo_title' => $tour->seo_title ?? '',
                    'seo_desc' => $tour->seo_desc ?? '',
                    'seo_keywords' => $tour->seo_keywords ?? '',
                    'canonical_url' => $tour->canonical_url ?? '',
                    'robots_meta' => $tour->robots_meta ?? '',
                    'schema_markup' => $tour->schema_markup ?? '',             
                    // OG fields
                    'og_title' => $tour->og_title ?? '',
                    'og_description' => $tour->og_description ?? '',
                    'og_image_id' => $tour->og_image_id ? (int)$tour->og_image_id : null,
                    'og_image_url' => $tour->og_image_url ?? '',
                    
                    // Twitter fields
                    'twitter_card' => $tour->twitter_card ?? 'summary_large_image',
                    'twitter_title' => $tour->twitter_title ?? '',
                    'twitter_description' => $tour->twitter_description ?? '',
                    'twitter_image_id' => $tour->twitter_image_id ? (int)$tour->twitter_image_id : null,
                    'twitter_image_url' => $tour->twitter_image_url ?? '',
                    
                    // Timestamps & Author
                    'created_at' => $tour->created_at,
                    'updated_at' => $tour->updated_at,
                    'author' => $tour->author ? [
                        'id' => (int) $tour->author->id,
                        'name' => $tour->author->getDisplayName() ?? $tour->author->name ?? $tour->author->first_name,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Create/Update tour (protected)
    Route::middleware('auth:sanctum')->post('/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $tour = Tour::findOrFail($id);
            } else {
                $tour = new Tour();
            }
            
            // Define allowed fields (only new field names, no duplicates)
            $allowedFields = [
                // Basic Info
                'title', 'slug', 'short_desc', 'status', 'is_featured',
                // Category & Location
                'category_ids', 'location_id',
                // Pricing
                'price', 'sale_price', 'pricing_type', 'group_price', 'child_price',
                // Duration & Capacity
                'duration', 'duration_type', 'nights', 'tour_type', 'min_people', 'max_people',
                // New Arrays
                'suitable_for', 'tour_themes', 'cities_covered', 'tour_expert_id',
                // Images
                'image_id', 'banner_image_id', 'banner_image_url', 'hero_slider',
                // Location Details
                'address', 'map_lat', 'map_lng', 'map_zoom', 'map_image_id', 'map_embed',
                // Content (new field names only)
                'inclusions', 'exclusions', 'highlights', 'itinerary', 'faqs',
                // Policies
                'conditions', 'cancellation_policy', 'child_policy', 'payment_terms',
                // Availability
                'min_day_before_booking', 'enable_fixed_date', 'start_date', 'end_date', 'last_booking_date',
                'availability_dates', 'related_tour_ids',
                // SEO
                'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'canonical_url', 'robots_meta', 'schema_markup',
                // OG
                'og_title', 'og_description', 'og_image', 'og_image_id', 'og_image_url',
                // Twitter
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image', 'twitter_image_id', 'twitter_image_url',
            ];
            
            $tour->fill($request->only($allowedFields));
            
            // Set author for new tours
            if (!$id) {
                $tour->create_user = $request->user()->id ?? 1;
            }
            
            $tour->save();
            
            return response()->json([
                'success' => true,
                'message' => $id ? 'Tour updated successfully' : 'Tour created successfully',
                'data' => ['id' => (int) $tour->id],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete tour (protected)
    Route::middleware('auth:sanctum')->delete('/{id}', function ($id) {
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
    
    // Get tour categories
    Route::get('/category', function () {
        try {
            $categories = TourCategory::where('status', 'publish')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'status', 'parent_id']);
            
            return response()->json(['data' => $categories]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    });
    
    // Create/Update category (protected)
    Route::middleware('auth:sanctum')->post('/category/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $category = TourCategory::findOrFail($id);
            } else {
                $category = new TourCategory();
            }
            
            $category->fill($request->only(['name', 'slug', 'status', 'parent_id']));
            $category->save();
            
            return response()->json([
                'success' => true,
                'data' => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete category (protected)
    Route::middleware('auth:sanctum')->delete('/category/{id}', function ($id) {
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
});

// Bulk Edit Tours (Admin)
Route::middleware('auth:sanctum')->post('/module/tour/bulkEdit', function (Request $request) {
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

// ========== TOUR THEMES (Travel Styles) MANAGEMENT ==========

// Get all tour themes for admin
Route::middleware('auth:sanctum')->get('/module/tour/themes', function () {
    try {
        // Get or create the travel-styles attribute
        $travelStylesAttr = \DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'travel-styles')
            ->first();
        
        if (!$travelStylesAttr) {
            // Create the attribute if it doesn't exist
            $attrId = \DB::table('bc_attrs')->insertGetId([
                'name' => 'Travel Styles',
                'slug' => 'travel-styles',
                'service' => 'tour',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $attrId = $travelStylesAttr->id;
        }
        
        $themes = \DB::table('bc_terms')
            ->where('attr_id', $attrId)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'slug', 'icon', 'image_id', 'attr_id', 'created_at')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'data' => $themes,
            'attr_id' => $attrId,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Store/Update tour theme
Route::middleware('auth:sanctum')->post('/module/tour/themes/store/{id?}', function (Request $request, $id = null) {
    try {
        // Get the attr_id
        $travelStylesAttr = \DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'travel-styles')
            ->first();
        
        if (!$travelStylesAttr) {
            $attrId = \DB::table('bc_attrs')->insertGetId([
                'name' => 'Travel Styles',
                'slug' => 'travel-styles',
                'service' => 'tour',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $attrId = $travelStylesAttr->id;
        }
        
        $data = [
            'name' => $request->input('name'),
            'slug' => $request->input('slug') ?: \Str::slug($request->input('name')),
            'icon' => $request->input('icon'),
            'image_id' => $request->input('image_id'),
            'attr_id' => $attrId,
            'updated_at' => now(),
        ];
        
        if ($id) {
            \DB::table('bc_terms')->where('id', $id)->update($data);
            $themeId = $id;
        } else {
            $data['created_at'] = now();
            $themeId = \DB::table('bc_terms')->insertGetId($data);
        }
        
        return response()->json([
            'success' => true,
            'data' => ['id' => $themeId],
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Delete tour theme
Route::middleware('auth:sanctum')->delete('/module/tour/themes/{id}', function ($id) {
    try {
        \DB::table('bc_terms')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// ========== TOUR EXPERTS (Users) ==========

// Get tour experts for dropdown
Route::middleware('auth:sanctum')->get('/module/tour/experts', function () {
    try {
        $users = \DB::table('users')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
        
        return response()->json(['data' => $users]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
