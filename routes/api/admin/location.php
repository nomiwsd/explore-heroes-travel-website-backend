<?php

/**
 * ADMIN LOCATION MODULE ROUTES
 * All location/destination-related admin functionality
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Location\Models\Location;

Route::prefix('module/location')->group(function () {
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
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'slug' => $loc->slug,
                    'content' => $loc->content,
                    'image_id' => $loc->image_id,
                    'image_url' => $loc->image_id ? get_file_url($loc->image_id, 'full') : null,
                    'map_lat' => $loc->map_lat,
                    'map_lng' => $loc->map_lng,
                    'map_zoom' => $loc->map_zoom,
                    'status' => $loc->status,
                    'parent_id' => $loc->parent_id,
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
    });
    
    // Get single location for editing
    Route::get('/edit/{id}', function ($id) {
        try {
            $loc = Location::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'slug' => $loc->slug,
                    'content' => $loc->content,
                    'image_id' => $loc->image_id,
                    'image_url' => $loc->image_id ? get_file_url($loc->image_id, 'full') : null,
                    'banner_image_id' => $loc->banner_image_id ?? null,
                    'map_lat' => $loc->map_lat,
                    'map_lng' => $loc->map_lng,
                    'map_zoom' => $loc->map_zoom,
                    'status' => $loc->status,
                    'parent_id' => $loc->parent_id,
                    'created_at' => $loc->created_at,
                    'updated_at' => $loc->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Create location
    Route::middleware('auth:sanctum')->post('/store', function (Request $request) {
        try {
            $loc = new Location();
            $loc->fill($request->only([
                'name', 'slug', 'content', 'image_id', 'banner_image_id',
                'map_lat', 'map_lng', 'map_zoom', 'status', 'parent_id'
            ]));
            $loc->create_user = $request->user()->id ?? 1;
            $loc->save();
            
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
    Route::middleware('auth:sanctum')->post('/store/{id}', function (Request $request, $id) {
        try {
            $loc = Location::findOrFail($id);
            $loc->fill($request->only([
                'name', 'slug', 'content', 'image_id', 'banner_image_id',
                'map_lat', 'map_lng', 'map_zoom', 'status', 'parent_id'
            ]));
            $loc->save();
            
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
    Route::middleware('auth:sanctum')->post('/bulkEdit', function (Request $request) {
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
