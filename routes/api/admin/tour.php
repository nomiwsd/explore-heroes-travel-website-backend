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
                    'max_people' => $tour->max_people,
                    'min_people' => $tour->min_people,
                    'category_id' => $tour->category_id,
                    'category_name' => $tour->category_tour ? $tour->category_tour->name : null,
                    'location_id' => $tour->location_id,
                    'location_name' => $tour->location ? $tour->location->name : null,
                    'address' => $tour->address,
                    'map_lat' => $tour->map_lat,
                    'map_lng' => $tour->map_lng,
                    'map_zoom' => $tour->map_zoom,
                    'status' => $tour->status,
                    'is_featured' => $tour->is_featured,
                    'faqs' => $tour->faqs,
                    'include' => $tour->include,
                    'exclude' => $tour->exclude,
                    'itinerary' => $tour->itinerary,
                    'surrounding' => $tour->surrounding,
                    'min_day_before_booking' => $tour->min_day_before_booking,
                    'enable_fixed_date' => $tour->enable_fixed_date,
                    'start_date' => $tour->start_date,
                    'end_date' => $tour->end_date,
                    'last_booking_date' => $tour->last_booking_date,
                    'created_at' => $tour->created_at,
                    'updated_at' => $tour->updated_at,
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
                'gallery', 'video', 'price', 'sale_price', 'duration',
                'max_people', 'min_people', 'category_id', 'location_id',
                'address', 'map_lat', 'map_lng', 'map_zoom', 'status',
                'is_featured', 'faqs', 'include', 'exclude', 'itinerary',
                'surrounding', 'min_day_before_booking', 'enable_fixed_date',
                'start_date', 'end_date', 'last_booking_date'
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
