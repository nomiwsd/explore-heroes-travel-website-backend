<?php

/**
 * ADMIN LOCATION MODULE ROUTES
 * All location/destination-related admin functionality
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Location\Models\Location;

Route::prefix('module/location')->middleware('auth:sanctum')->group(function () {
    // Get all locations (for admin)
    Route::get('/', function (Request $request) {
        try {
            $query = Location::query();
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where('name', 'LIKE', '%' . $request->s . '%');
            }
            
            // Status filter
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            $perPage = $request->per_page ?? 20;
            $locations = $query->orderBy('id', 'desc')->paginate($perPage);
            
            // Transform data
            $data = $locations->map(function ($loc) {
                // Get image URL - ensure null if not found
                $imageUrl = null;
                if ($loc->image_id) {
                    $url = get_file_url($loc->image_id, 'full');
                    $imageUrl = $url ?: null;
                }
                
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'slug' => $loc->slug,
                    'content' => $loc->content,
                    'image_id' => $loc->image_id,
                    'image_url' => $imageUrl,
                    'map_lat' => $loc->map_lat,
                    'map_lng' => $loc->map_lng,
                    'map_zoom' => $loc->map_zoom,
                    'status' => $loc->status,
                    'parent_id' => $loc->parent_id,
                    'is_featured' => $loc->is_featured,
                    'show_on_homepage' => $loc->show_on_homepage,
                    'destination_type' => $loc->destination_type,
                    'display_order' => $loc->display_order,
                    'short_description' => $loc->translate()->short_description,
                    'created_at' => $loc->created_at,
                    'updated_at' => $loc->updated_at,
                ];
            });
            
            return response()->json([
                'data' => $data,
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
    })->middleware('permission:location_view');
    
    // Get single location for editing
    Route::get('/edit/{id}', function ($id) {
        try {
            $loc = Location::findOrFail($id);
            
            // Get image URLs - ensure null if not found
            $imageUrl = null;
            if ($loc->image_id) {
                $url = get_file_url($loc->image_id, 'full');
                $imageUrl = $url ?: null;
            }
            
            $bannerImageUrl = null;
            if ($loc->banner_image_id) {
                $url = get_file_url($loc->banner_image_id, 'full');
                $bannerImageUrl = $url ?: null;
            }
            
            $seo = $loc->getSeoMeta();
            
            return response()->json([
                'data' => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'slug' => $loc->slug,
                    'content' => $loc->translate()->content,
                    'image_id' => $loc->image_id,
                    'image_url' => $imageUrl,
                    'banner_image_id' => $loc->banner_image_id ?? null,
                    'banner_image_url' => $bannerImageUrl,
                    'gallery' => $loc->gallery,
                    'map_lat' => $loc->map_lat,
                    'map_lng' => $loc->map_lng,
                    'map_zoom' => $loc->map_zoom,
                    'status' => $loc->status,
                    'parent_id' => $loc->parent_id,
                    'is_featured' => $loc->is_featured,
                    'show_on_homepage' => $loc->show_on_homepage,
                    'destination_type' => $loc->destination_type ?? 'city',
                    'display_order' => $loc->display_order ?? 0,
                    'short_description' => $loc->translate()->short_description,
                    'seo_title' => $seo['seo_title'] ?? '',
                    'seo_desc' => $seo['seo_desc'] ?? '',
                    'tours' => $loc->tours->map(function($tl){
                        return ['id' => $tl->tour_id];
                    }),
                    'created_at' => $loc->created_at,
                    'updated_at' => $loc->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('permission:location_view');
    
    // Create location
    Route::middleware(['auth:sanctum', 'permission:location_create'])->post('/store', function (Request $request) {
        try {
            $loc = new Location();
            $loc->fill($request->only([
                'name', 'slug', 'content', 'image_id', 'banner_image_id',
                'map_lat', 'map_lng', 'map_zoom', 'status', 'parent_id',
                'is_featured', 'show_on_homepage', 'destination_type', 'display_order', 'gallery'
            ]));
            $loc->create_user = $request->user()->id ?? 1;
            $loc->save();

            // Save translation
            $translation = $loc->translate($request->input('lang', 'en'));
            $translation->name = $request->input('name');
            $translation->content = $request->input('content');
            $translation->short_description = $request->input('short_description');
            $translation->save();

            // SEO
            $loc->saveSEO($request);

            // Tours
            $tours = $request->input('assigned_tour_ids');
            if (is_array($tours)) {
                Modules\Tour\Models\TourLocation::where('location_id', $loc->id)->delete();
                foreach ($tours as $tour_id) {
                    $tl = new Modules\Tour\Models\TourLocation();
                    $tl->location_id = $loc->id;
                    $tl->tour_id = $tour_id;
                    $tl->save();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'data' => ['id' => $loc->id],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update location
    Route::middleware(['auth:sanctum', 'permission:location_update'])->post('/store/{id}', function (Request $request, $id) {
        try {
            $loc = Location::findOrFail($id);
            $loc->fill($request->only([
                'name', 'slug', 'content', 'image_id', 'banner_image_id',
                'map_lat', 'map_lng', 'map_zoom', 'status', 'parent_id',
                'is_featured', 'show_on_homepage', 'destination_type', 'display_order', 'gallery'
            ]));
            $loc->save();

            // Save translation
            $translation = $loc->translate($request->input('lang', 'en'));
            $translation->name = $request->input('name');
            $translation->content = $request->input('content');
            $translation->short_description = $request->input('short_description');
            $translation->save();

            // SEO
            $loc->saveSEO($request);

            // Tours
            $tours = $request->input('assigned_tour_ids');
            if (is_array($tours)) {
                Modules\Tour\Models\TourLocation::where('location_id', $loc->id)->delete();
                foreach ($tours as $tour_id) {
                    $tl = new Modules\Tour\Models\TourLocation();
                    $tl->location_id = $loc->id;
                    $tl->tour_id = $tour_id;
                    $tl->save();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => ['id' => $loc->id],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit (delete, publish, draft)
    Route::middleware(['auth:sanctum', 'permission:location_update'])->post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    Location::whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    Location::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    Location::whereIn('id', $ids)->update(['status' => 'draft']);
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
