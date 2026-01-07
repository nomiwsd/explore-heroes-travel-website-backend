<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Settings;
use Modules\Language\Models\Language;
use Modules\Core\Models\Menu;
use Modules\Location\Models\Location;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;

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

// =====================================================
// PUBLIC MENU API (no auth required)
// =====================================================
Route::prefix('menus')->group(function () {
    // Get menu by location (header, footer, etc.)
    Route::get('/location/{location}', function ($location) {
        try {
            $menu = Menu::where('status', 'publish')
                ->whereRaw("JSON_CONTAINS(locations, '\"$location\"')")
                ->first();
            
            if (!$menu) {
                return response()->json(null);
            }
            
            return response()->json([
                'id' => $menu->id,
                'name' => $menu->name,
                'items' => $menu->items_json,
                'locations' => $menu->locations,
            ]);
        } catch (\Exception $e) {
            return response()->json(null);
        }
    });
    
    // Get all published menus
    Route::get('/', function () {
        try {
            $menus = Menu::where('status', 'publish')->get();
            return response()->json($menus->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'items' => $menu->items_json,
                    'locations' => $menu->locations,
                ];
            }));
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
});

// =====================================================
// PUBLIC DESTINATIONS API (no auth required)
// =====================================================
Route::prefix('destinations')->group(function () {
    // Get all published destinations
    Route::get('/', function (Request $request) {
        try {
            $query = Location::where('status', 'publish');
            
            $destinations = $query->orderBy('name', 'asc')->get();
            
            return response()->json([
                'data' => $destinations->map(function ($dest) {
                    return [
                        'id' => $dest->id,
                        'name' => $dest->name,
                        'slug' => $dest->slug,
                        'content' => $dest->content,
                        'image_url' => $dest->image_id ? get_file_url($dest->image_id, 'full') : null,
                        'map_lat' => $dest->map_lat,
                        'map_lng' => $dest->map_lng,
                        'tours_count' => \Modules\Tour\Models\Tour::where('location_id', $dest->id)->where('status', 'publish')->count(),
                    ];
                }),
                'total' => $destinations->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get single destination by slug
    Route::get('/{slug}', function ($slug) {
        try {
            $destination = Location::where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$destination) {
                return response()->json(['error' => 'Destination not found'], 404);
            }
            
            // Get related tours
            $tours = \Modules\Tour\Models\Tour::where('location_id', $destination->id)
                ->where('status', 'publish')
                ->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'data' => [
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'slug' => $destination->slug,
                    'content' => $destination->content,
                    'image_url' => $destination->image_id ? get_file_url($destination->image_id, 'full') : null,
                    'map_lat' => $destination->map_lat,
                    'map_lng' => $destination->map_lng,
                    'tours' => $tours->map(function ($tour) {
                        return [
                            'id' => $tour->id,
                            'title' => $tour->title,
                            'slug' => $tour->slug,
                            'price' => $tour->price,
                            'sale_price' => $tour->sale_price,
                            'duration' => $tour->duration,
                            'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// PUBLIC TOUR FILTERS API (no auth required)
// =====================================================

// Get tour categories (tour types)
Route::get('/tour-categories', function () {
    try {
        $categories = \DB::table('bc_tour_category')
            ->where('status', 'publish')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'data' => $categories,
            'total' => $categories->count(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
    }
});

// Get tour attributes/themes (travel styles)
Route::get('/tour-themes', function () {
    try {
        // Get the "Travel Styles" attribute which is tour theme
        $travelStylesAttr = \DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'travel-styles')
            ->first();
        
        if (!$travelStylesAttr) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        
        $themes = \DB::table('bc_terms')
            ->where('attr_id', $travelStylesAttr->id)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'slug', 'icon', 'image_id')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'data' => $themes,
            'total' => $themes->count(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
    }
});

// Get tour facilities/features
Route::get('/tour-facilities', function () {
    try {
        // Get the "Facilities" attribute for tours
        $facilitiesAttr = \DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'facilities')
            ->first();
        
        if (!$facilitiesAttr) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        
        $facilities = \DB::table('bc_terms')
            ->where('attr_id', $facilitiesAttr->id)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'slug', 'icon', 'image_id')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'data' => $facilities,
            'total' => $facilities->count(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
    }
});

// =====================================================
// PUBLIC TOURS API (no auth required)
// =====================================================
Route::prefix('tours')->group(function () {
    // Get all published tours
    Route::get('/', function (Request $request) {
        try {
            $query = Tour::where('status', 'publish');
            
            // Filter by destination
            if ($request->has('destination_id')) {
                $query->where('location_id', $request->destination_id);
            }
            
            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            // Filter by featured
            if ($request->has('featured') && $request->featured == '1') {
                $query->where('is_featured', 1);
            }
            
            // Search
            if ($request->has('s') && $request->s) {
                $query->where('title', 'like', '%' . $request->s . '%');
            }
            
            // Pagination
            $perPage = $request->get('per_page', 12);
            $tours = $query->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            return response()->json([
                'data' => $tours->map(function ($tour) {
                    return [
                        'id' => $tour->id,
                        'title' => $tour->title,
                        'slug' => $tour->slug,
                        'short_description' => $tour->short_desc,
                        'price' => $tour->price,
                        'sale_price' => $tour->sale_price,
                        'duration' => $tour->duration,
                        'duration_nights' => $tour->duration ? $tour->duration - 1 : null,
                        'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                        'is_featured' => $tour->is_featured,
                        'tour_type' => $tour->tour_type,
                        'destination' => $tour->location ? [
                            'id' => $tour->location->id,
                            'name' => $tour->location->name,
                            'slug' => $tour->location->slug,
                        ] : null,
                        'category' => $tour->category ? [
                            'id' => $tour->category->id,
                            'name' => $tour->category->name,
                        ] : null,
                    ];
                }),
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get single tour by slug
    Route::get('/{slug}', function ($slug) {
        try {
            $tour = Tour::where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$tour) {
                return response()->json(['error' => 'Tour not found'], 404);
            }
            
            // Get itinerary
            $itinerary = [];
            if ($tour->itinerary) {
                $itinerary = is_string($tour->itinerary) ? json_decode($tour->itinerary, true) : $tour->itinerary;
            }
            
            // Get FAQs
            $faqs = [];
            if ($tour->faqs) {
                $faqs = is_string($tour->faqs) ? json_decode($tour->faqs, true) : $tour->faqs;
            }
            
            // Get include/exclude
            $include = [];
            $exclude = [];
            if ($tour->include) {
                $include = is_string($tour->include) ? json_decode($tour->include, true) : $tour->include;
            }
            if ($tour->exclude) {
                $exclude = is_string($tour->exclude) ? json_decode($tour->exclude, true) : $tour->exclude;
            }
            
            // Get gallery images
            $gallery = [];
            if ($tour->gallery) {
                $galleryIds = is_string($tour->gallery) ? json_decode($tour->gallery, true) : $tour->gallery;
                if (is_array($galleryIds)) {
                    $gallery = array_map(function($id) {
                        return get_file_url($id, 'full');
                    }, $galleryIds);
                }
            }
            
            // Get related tours
            $relatedTours = Tour::where('status', 'publish')
                ->where('id', '!=', $tour->id)
                ->where(function($q) use ($tour) {
                    $q->where('location_id', $tour->location_id)
                      ->orWhere('category_id', $tour->category_id);
                })
                ->limit(4)
                ->get();
            
            return response()->json([
                'data' => [
                    'id' => $tour->id,
                    'title' => $tour->title,
                    'slug' => $tour->slug,
                    'short_description' => $tour->short_desc,
                    'content' => $tour->content,
                    'price' => $tour->price,
                    'sale_price' => $tour->sale_price,
                    'duration' => $tour->duration,
                    'duration_nights' => $tour->duration ? $tour->duration - 1 : null,
                    'group_size' => $tour->max_people,
                    'tour_type' => $tour->tour_type,
                    'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                    'banner_url' => $tour->banner_image_id ? get_file_url($tour->banner_image_id, 'full') : null,
                    'video_url' => $tour->video,
                    'map_lat' => $tour->map_lat,
                    'map_lng' => $tour->map_lng,
                    'is_featured' => $tour->is_featured,
                    'destination' => $tour->location ? [
                        'id' => $tour->location->id,
                        'name' => $tour->location->name,
                        'slug' => $tour->location->slug,
                    ] : null,
                    'category' => $tour->category ? [
                        'id' => $tour->category->id,
                        'name' => $tour->category->name,
                    ] : null,
                    'gallery' => $gallery,
                    'itinerary' => $itinerary,
                    'include' => $include,
                    'exclude' => $exclude,
                    'faqs' => $faqs,
                    'highlights' => $tour->highlights,
                    'meta_title' => $tour->meta_title ?? $tour->title,
                    'meta_description' => $tour->meta_desc ?? $tour->short_desc,
                    'related_tours' => $relatedTours->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'title' => $t->title,
                            'slug' => $t->slug,
                            'price' => $t->price,
                            'duration' => $t->duration,
                            'image_url' => $t->image_id ? get_file_url($t->image_id, 'full') : null,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// Public Settings API (no auth required)
Route::prefix('settings')->group(function () {
    Route::get('/{group?}', function ($group = 'general') {
        $settings = Settings::getSettings($group);
        return response()->json($settings);
    });
});

// Public Translations API (no auth required)
Route::prefix('translations')->group(function () {
    // Get all active languages
    Route::get('/languages', function () {
        try {
            $languages = Language::where('status', 'publish')
                ->orderByRaw('CASE WHEN is_default = 1 THEN 0 ELSE 1 END')
                ->get(['id', 'locale', 'name', 'flag', 'is_default', 'status']);

            return response()->json($languages);
        } catch (\Exception $e) {
            // Return default English if table doesn't exist
            return response()->json([
                ['id' => 1, 'locale' => 'en', 'name' => 'English', 'flag' => '🇬🇧', 'is_default' => 1, 'status' => 'publish']
            ]);
        }
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
});

// Test Routes - For Database Testing
Route::prefix('test')->group(function () {
    Route::get('/health', [TestController::class, 'health']);
    Route::get('/database', [TestController::class, 'testDatabase']);
    Route::get('/table/{tableName}', [TestController::class, 'getTableData']);
    Route::get('/users', [TestController::class, 'getUsers']);
    Route::get('/bookings', [TestController::class, 'getBookings']);
});

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    // Login
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url ?? null,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    });

    // Logout
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    })->middleware('auth:sanctum');

    // Get authenticated user
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    })->middleware('auth:sanctum');

    // Dashboard Routes (protected)
    Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/submissions', [DashboardController::class, 'getLatestSubmissions']);
        Route::get('/activity', [DashboardController::class, 'getActivityLog']);
        Route::get('/website-status', [DashboardController::class, 'getWebsiteStatus']);
        Route::post('/submissions/{id}/status', [DashboardController::class, 'updateSubmissionStatus']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// =====================================================
// MODULE TOUR API (Admin functionality - protected)
// =====================================================

Route::prefix('module/tour')->group(function () {
    // Get all tours (can be public for listing, or protected)
    Route::get('/', function (Request $request) {
        try {
            $query = Tour::with(['location', 'category_tour']);
            
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
