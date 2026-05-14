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
            $lang = $request->query('lang');

            // Transform data
            $data = $locations->getCollection()->map(function ($loc) use ($lang) {
                // Get image URL - ensure null if not found
                $imageUrl = null;
                if ($loc->image_id) {
                    $url = get_file_url($loc->image_id, 'full');
                    $imageUrl = $url ?: null;
                }

                $translation = ($lang && !is_default_lang($lang)) ? $loc->forceTranslate($lang) : null;

                return [
                    'id' => $loc->id,
                    'name' => $translation->name ?? $loc->name,
                    'slug' => $loc->slug,
                    'content' => $translation->content ?? $loc->content,
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
                    'short_description' => $translation->short_description ?? ($loc->translate()->short_description ?? null),
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
    Route::get('/edit/{id}', function (Request $request, $id) {
        try {
            $loc = Location::findOrFail($id);

            // Get translation if lang param is provided
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $loc->forceTranslate($lang);
            }

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

            $responseData = [
                'data' => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'slug' => $loc->slug,
                    'content' => $loc->content,
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
                    'short_description' => $loc->short_description, // Use model property (which checks translation) or field directly
                    'seo_title' => $seo['seo_title'] ?? '',
                    'seo_desc' => $seo['seo_desc'] ?? '',
                    'tours' => $loc->tours->map(function($tl){
                        return ['id' => $tl->tour_id];
                    }),
                    'created_at' => $loc->created_at,
                    'updated_at' => $loc->updated_at,
                ],
            ];

            // Include translation if fetched
            if ($translation) {
                $responseData['translation'] = $translation;

                // Also include the per-locale SEO row so the admin form pre-fills
                // its SEO fields with previously saved translated values.
                $translationSeo = \Modules\Core\Models\SEO::where('object_id', $translation->id)
                    ->where('object_model', 'location_translation_' . $lang)
                    ->first();
                if ($translationSeo) {
                    $responseData['translation_seo'] = $translationSeo;
                }
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('permission:location_view');

    // Create/Update location
    Route::middleware(['auth:sanctum', 'permission:location_update'])->post('/store/{id?}', function (Request $request, $id = null) {
        try {
            $lang = $request->query('lang') ?: $request->input('lang');
            $isTranslation = $lang && !is_default_lang($lang);

            if ($id) {
                $loc = Location::findOrFail($id);
            } else {
                $loc = new Location();
            }

            $loc->fill($request->only([
                'name', 'slug', 'content', 'image_id', 'banner_image_id',
                'map_lat', 'map_lng', 'map_zoom', 'status', 'parent_id',
                'is_featured',
                'show_on_homepage',
                'destination_type',
                'display_order',
                'gallery',
                'short_description'
            ]));

            if (!$id) {
                $loc->create_user = $request->user()->id ?? 1;
            }

            if ($isTranslation) {
                // For translations, don't save the main model, only save translation
                $translation = $loc->forceSaveTranslation($lang, $request->input());
                // Persist per-locale SEO (object_model = location_translation_{lang})
                // so admin's seo_title / seo_desc / og / twitter edits on the
                // translation tab actually save and flow to the public page.
                $translation->saveSEO($request, $lang);
                $message = 'Translation saved successfully';
            } else {
                // For default language, save both main model and translation
                $loc->save();
                $loc->forceSaveTranslation($lang ?: 'en', $request->input());
                $loc->saveSEO($request);

                // Save tours only for main language/record
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

                $message = $id ? 'Location updated successfully' : 'Location created successfully';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
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
                    // Retrieve models first to ensure events and traits (SoftDeletes, NodeTrait) are triggered
                    $locations = Location::whereIn('id', $ids)->get();
                    foreach ($locations as $location) {
                        $location->delete();
                    }
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
