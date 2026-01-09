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
                    'gallery_count' => is_array($tour->gallery) ? count($tour->gallery) : 0, // Added
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
            $tour = Tour::with(['location', 'category_tour'])->findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $tour->id,
                    'title' => $tour->title,
                    'slug' => $tour->slug,
                    'content' => $tour->content,
                    'short_desc' => $tour->short_desc,
                    'image_id' => $tour->image_id,
                    'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                    'banner_image_id' => $tour->banner_image_id,
                    'banner_image_url' => $tour->banner_image_id ? get_file_url($tour->banner_image_id, 'full') : null,
                    'gallery' => $tour->gallery,
                    'video' => $tour->video,
                    'price' => $tour->price,
                    'sale_price' => $tour->sale_price,
                    'duration' => $tour->duration,
                    'duration_type' => $tour->duration_type ?? 'days',
                    'nights' => $tour->nights,
                    'tour_type' => $tour->tour_type,
                    'max_people' => $tour->max_people,
                    'min_people' => $tour->min_people,
                    'pricing_type' => $tour->pricing_type ?? 'per_person',
                    'group_price' => $tour->group_price,
                    'child_price' => $tour->child_price,
                    'suitable_for' => $tour->suitable_for,
                    'tour_themes' => $tour->tour_themes,
                    'cities_covered' => $tour->cities_covered,
                    'summary_inclusions' => $tour->summary_inclusions,
                    'tour_expert_id' => $tour->tour_expert_id,
                    'category_id' => $tour->category_id,
                    'category_name' => $tour->category_tour ? $tour->category_tour->name : null,
                    'location_id' => $tour->location_id,
                    'location_name' => $tour->location ? $tour->location->name : null,
                    'address' => $tour->address,
                    'map_lat' => $tour->map_lat,
                    'map_lng' => $tour->map_lng,
                    'map_zoom' => $tour->map_zoom,
                    'map_image_id' => $tour->map_image_id,
                    'map_image_url' => $tour->map_image_id ? get_file_url($tour->map_image_id, 'full') : null,
                    'map_embed' => $tour->map_embed,
                    'status' => $tour->status,
                    'is_featured' => $tour->is_featured,
                    'faqs' => $tour->faqs,
                    'include' => $tour->include,
                    'exclude' => $tour->exclude,
                    'inclusions' => $tour->inclusions, // Added
                    'exclusions' => $tour->exclusions, // Added
                    'itinerary' => $tour->itinerary,
                    'highlight' => $tour->highlight,
                    'highlights' => $tour->highlights, // Added
                    'surrounding' => $tour->surrounding,
                    'conditions' => $tour->conditions,
                    'cancellation_policy' => $tour->cancellation_policy,
                    'child_policy' => $tour->child_policy,
                    'payment_terms' => $tour->payment_terms,
                    'hero_slider' => $tour->hero_slider,
                    'min_day_before_booking' => $tour->min_day_before_booking,
                    'enable_fixed_date' => $tour->enable_fixed_date,
                    'start_date' => $tour->start_date,
                    'end_date' => $tour->end_date,
                    'last_booking_date' => $tour->last_booking_date,
                    'availability_dates' => $tour->availability_dates,
                    'related_tour_ids' => $tour->related_tour_ids,
                    // SEO fields
                    'seo_title' => $tour->seo_title,
                    'seo_desc' => $tour->seo_desc,
                    'seo_image' => $tour->seo_image,
                    // OG fields
                    'og_title' => $tour->og_title,
                    'og_description' => $tour->og_description,
                    'og_image' => $tour->og_image,
                    // Twitter fields
                    'twitter_card' => $tour->twitter_card,
                    'twitter_title' => $tour->twitter_title,
                    'twitter_description' => $tour->twitter_description,
                    'twitter_image' => $tour->twitter_image,
                    'created_at' => $tour->created_at,
                    'updated_at' => $tour->updated_at,
                    'author' => $tour->author ? [
                        'id' => $tour->author->id,
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
            
            $tour->fill($request->only([
                'title', 'content', 'short_desc', 'image_id', 'banner_image_id',
                'gallery', 'video', 'price', 'sale_price', 'duration', 'nights',
                'tour_type', 'max_people', 'min_people', 'category_id', 'location_id',
                'address', 'map_lat', 'map_lng', 'map_zoom', 'map_image_id', 'status',
                'is_featured', 'faqs', 'include', 'exclude', 'itinerary', 'highlight',
                'surrounding', 'suitable_for', 'tour_themes', 'cities_covered',
                'summary_inclusions', 'tour_expert_id', 'conditions', 'cancellation_policy',
                'child_policy', 'payment_terms', 'hero_slider', 'map_embed',
                'min_day_before_booking', 'enable_fixed_date',
                'start_date', 'end_date', 'last_booking_date',
                'duration_type', 'pricing_type', 'group_price', 'child_price',
                'seo_title', 'seo_desc', 'seo_image', 'seo_share',
                'og_title', 'og_description', 'og_image',
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
                'availability_dates', 'related_tour_ids', 'inclusions', 'exclusions', 'highlights'
            ]));
            
            $tour->create_user = $request->user()->id ?? 1;
            $tour->save();
            
            return response()->json([
                'success' => true,
                'message' => $id ? 'Tour updated successfully' : 'Tour created successfully',
                'data' => ['id' => $tour->id],
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
