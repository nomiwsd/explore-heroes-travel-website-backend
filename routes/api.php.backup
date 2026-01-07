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
            
            // Filter by is_featured
            if ($request->has('featured') && $request->featured == '1') {
                $query->where('is_featured', 1);
            }
            
            // Filter by show_on_homepage
            if ($request->has('homepage') && $request->homepage == '1') {
                // Use is_featured as homepage display indicator
                $query->where('is_featured', 1);
            }
            
            // Filter by destination type
            if ($request->has('type')) {
                // Add type filter if the column exists
                // $query->where('destination_type', $request->type);
            }
            
            $destinations = $query->orderBy('name', 'asc')->get();
            
            return response()->json([
                'data' => $destinations->map(function ($dest) {
                    return [
                        'id' => $dest->id,
                        'name' => $dest->name,
                        'slug' => $dest->slug,
                        'content' => $dest->content,
                        'short_description' => $dest->content ? \Illuminate\Support\Str::limit(strip_tags($dest->content), 150) : null,
                        'image_url' => $dest->image_id ? get_file_url($dest->image_id, 'full') : null,
                        'banner_url' => $dest->banner_image_id ? get_file_url($dest->banner_image_id, 'full') : null,
                        'map_lat' => $dest->map_lat,
                        'map_lng' => $dest->map_lng,
                        'is_featured' => $dest->is_featured,
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

// =====================================================
// PUBLIC REVIEWS API (no auth required - for homepage testimonials)
// =====================================================
Route::prefix('reviews')->group(function () {
    // Get approved/featured reviews for homepage
    Route::get('/', function (Request $request) {
        try {
            $query = \DB::table('bc_review')
                ->where('status', 'approved');
            
            // Filter by featured
            if ($request->has('featured') && $request->featured === 'featured') {
                $query->where(function($q) {
                    $q->where('is_featured', 1)
                      ->orWhere('show_on_homepage', 1);
                });
            }
            
            // Filter by object model (tour, hotel, etc)
            if ($request->has('object_model') && $request->object_model) {
                $query->where('object_model', $request->object_model);
            }
            
            $reviews = $query->orderBy('created_at', 'desc')
                ->limit($request->per_page ?? 20)
                ->get();
            
            // Transform to match frontend expectations
            $data = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'object_id' => $review->object_id,
                    'object_model' => $review->object_model ?? 'tour',
                    'tour_id' => $review->object_id,
                    'tour_name' => $review->tour_name ?? null,
                    'author_id' => $review->author_id,
                    'author_name' => $review->author_name ?? 'Anonymous',
                    'author_email' => $review->author_email ?? null,
                    'author_avatar' => $review->author_avatar ?? null,
                    'author_location' => $review->author_location ?? null,
                    'author_country' => $review->author_country ?? null,
                    'rating' => $review->rate_number ?? $review->rating ?? 5,
                    'title' => $review->title ?? '',
                    'content' => $review->content ?? '',
                    'status' => $review->status,
                    'show_on_homepage' => $review->show_on_homepage ?? 0,
                    'show_on_tour_page' => $review->show_on_tour_page ?? 0,
                    'is_featured' => $review->is_featured ?? 0,
                    'review_date' => $review->review_date ?? $review->created_at,
                    'review_source' => $review->review_source ?? 'website',
                    'trip_summary' => $review->trip_summary ?? null,
                    'created_at' => $review->created_at,
                ];
            });
            
            return response()->json([
                'data' => $data,
                'total' => $reviews->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get reviews for a specific tour/object (public)
    Route::get('/{objectModel}/{objectId}', function ($objectModel, $objectId) {
        try {
            $reviews = \DB::table('bc_review')
                ->where('object_model', $objectModel)
                ->where('object_id', $objectId)
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            return response()->json(['data' => $reviews]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
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

// =====================================================
// MODULE LOCATION API (Admin functionality)
// =====================================================
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

// =====================================================
// MODULE MENU API (Admin functionality)
// =====================================================
Route::prefix('module/core/menu')->group(function () {
    // Get all menus
    Route::get('/', function (Request $request) {
        try {
            $query = Menu::query();
            
            if ($request->has('s') && $request->s) {
                $query->where('name', 'LIKE', '%' . $request->s . '%');
            }
            
            $menus = $query->orderBy('id', 'desc')->get();
            
            return response()->json([
                'data' => $menus->map(function ($menu) {
                    return [
                        'id' => $menu->id,
                        'name' => $menu->name,
                        'items' => $menu->items_json,
                        'locations' => $menu->locations,
                        'status' => $menu->status,
                        'created_at' => $menu->created_at,
                    ];
                }),
                'total' => $menus->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single menu
    Route::get('/edit/{id}', function ($id) {
        try {
            $menu = Menu::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'items' => $menu->items_json,
                    'locations' => $menu->locations,
                    'status' => $menu->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store/Update menu
    Route::middleware('auth:sanctum')->post('/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $menu = Menu::findOrFail($id);
            } else {
                $menu = new Menu();
            }
            
            $menu->name = $request->input('name');
            $menu->items = $request->input('items');
            $menu->locations = $request->input('locations', []);
            $menu->status = $request->input('status', 'publish');
            $menu->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $menu->id],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete menu
    Route::middleware('auth:sanctum')->delete('/{id}', function ($id) {
        try {
            $menu = Menu::findOrFail($id);
            $menu->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit menus
    Route::middleware('auth:sanctum')->post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    Menu::whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    Menu::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    Menu::whereIn('id', $ids)->update(['status' => 'draft']);
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
    
    // Get menu item types
    Route::get('/getTypes', function () {
        try {
            // Define available item types for menu builder
            $types = [
                [
                    'key' => 'custom',
                    'name' => 'Custom Link',
                    'items' => [],
                ],
                [
                    'key' => 'page',
                    'name' => 'Pages',
                    'items' => \DB::table('bc_pages')
                        ->where('status', 'publish')
                        ->select('id', 'title as name', 'slug')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/' . $item->slug,
                            ];
                        }),
                ],
                [
                    'key' => 'tour',
                    'name' => 'Tours',
                    'items' => Tour::where('status', 'publish')
                        ->select('id', 'title as name', 'slug')
                        ->limit(50)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/tours/' . $item->slug,
                            ];
                        }),
                ],
                [
                    'key' => 'location',
                    'name' => 'Destinations',
                    'items' => Location::where('status', 'publish')
                        ->select('id', 'name', 'slug')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/destinations/' . $item->slug,
                            ];
                        }),
                ],
                [
                    'key' => 'news',
                    'name' => 'News/Blog',
                    'items' => \DB::table('bc_news')
                        ->where('status', 'publish')
                        ->select('id', 'title as name', 'slug')
                        ->limit(50)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/blog/' . $item->slug,
                            ];
                        }),
                ],
                [
                    'key' => 'tour_category',
                    'name' => 'Tour Categories',
                    'items' => TourCategory::where('status', 'publish')
                        ->select('id', 'name', 'slug')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/tours?category=' . $item->slug,
                            ];
                        }),
                ],
            ];
            
            return response()->json($types);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Search items by type
    Route::get('/searchItems', function (Request $request) {
        try {
            $type = $request->input('type');
            $query = $request->input('q', '');
            
            $items = [];
            
            switch ($type) {
                case 'page':
                    $items = \DB::table('bc_pages')
                        ->where('status', 'publish')
                        ->where('title', 'LIKE', '%' . $query . '%')
                        ->select('id', 'title as name', 'slug')
                        ->limit(20)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/' . $item->slug,
                            ];
                        });
                    break;
                case 'tour':
                    $items = Tour::where('status', 'publish')
                        ->where('title', 'LIKE', '%' . $query . '%')
                        ->select('id', 'title as name', 'slug')
                        ->limit(20)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/tours/' . $item->slug,
                            ];
                        });
                    break;
                case 'location':
                    $items = Location::where('status', 'publish')
                        ->where('name', 'LIKE', '%' . $query . '%')
                        ->select('id', 'name', 'slug')
                        ->limit(20)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/destinations/' . $item->slug,
                            ];
                        });
                    break;
            }
            
            return response()->json($items);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Get items by type
    Route::get('/getItems/{type}', function ($type, Request $request) {
        try {
            $page = $request->input('page', 1);
            $perPage = 20;
            $items = [];
            $total = 0;
            
            switch ($type) {
                case 'page':
                    $query = \DB::table('bc_pages')->where('status', 'publish');
                    $total = $query->count();
                    $items = $query->select('id', 'title as name', 'slug')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/' . $item->slug,
                            ];
                        });
                    break;
                case 'tour':
                    $query = Tour::where('status', 'publish');
                    $total = $query->count();
                    $items = $query->select('id', 'title as name', 'slug')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/tours/' . $item->slug,
                            ];
                        });
                    break;
                case 'location':
                    $query = Location::where('status', 'publish');
                    $total = $query->count();
                    $items = $query->select('id', 'name', 'slug')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'url' => '/destinations/' . $item->slug,
                            ];
                        });
                    break;
            }
            
            return response()->json([
                'data' => $items,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0]);
        }
    });
});

// =====================================================
// MODULE LANGUAGE API (Admin functionality)
// =====================================================
Route::prefix('module/language')->group(function () {
    // Get all languages
    Route::get('/', function (Request $request) {
        try {
            $languages = Language::orderBy('id', 'asc')->get();
            
            return response()->json([
                'data' => $languages->map(function ($lang) {
                    return [
                        'id' => $lang->id,
                        'locale' => $lang->locale,
                        'name' => $lang->name,
                        'flag' => $lang->flag,
                        'status' => $lang->status,
                        'is_default' => $lang->is_default,
                        'created_at' => $lang->created_at,
                    ];
                }),
                'total' => $languages->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get single language
    Route::get('/edit/{id}', function ($id) {
        try {
            $lang = Language::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $lang->id,
                    'locale' => $lang->locale,
                    'name' => $lang->name,
                    'flag' => $lang->flag,
                    'status' => $lang->status,
                    'is_default' => $lang->is_default,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Create language
    Route::middleware('auth:sanctum')->post('/store', function (Request $request) {
        try {
            $lang = new Language();
            $lang->locale = $request->input('locale');
            $lang->name = $request->input('name');
            $lang->flag = $request->input('flag');
            $lang->status = $request->input('status', 'publish');
            $lang->is_default = 0;
            $lang->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Language created successfully',
                'data' => ['id' => $lang->id],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update language
    Route::middleware('auth:sanctum')->post('/store/{id}', function (Request $request, $id) {
        try {
            $lang = Language::findOrFail($id);
            $lang->locale = $request->input('locale', $lang->locale);
            $lang->name = $request->input('name', $lang->name);
            $lang->flag = $request->input('flag', $lang->flag);
            $lang->status = $request->input('status', $lang->status);
            $lang->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully',
                'data' => ['id' => $lang->id],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit
    Route::middleware('auth:sanctum')->post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    // Don't delete default language
                    Language::whereIn('id', $ids)->where('is_default', '!=', 1)->delete();
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
    
    // Set default language
    Route::middleware('auth:sanctum')->post('/setDefault', function (Request $request) {
        try {
            $id = $request->input('id');
            
            // Remove default from all
            Language::where('is_default', 1)->update(['is_default' => 0]);
            
            // Set new default
            Language::where('id', $id)->update(['is_default' => 1]);
            
            return response()->json([
                'success' => true,
                'message' => 'Default language updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// MODULE SETTINGS API (Admin functionality)
// =====================================================
Route::prefix('module/core/settings')->group(function () {
    // Get settings by group
    Route::get('/index/{group?}', function ($group = 'general') {
        try {
            $settings = Settings::getSettings($group);
            return response()->json([
                'settings' => $settings,
                'group' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json(['settings' => [], 'group' => $group, 'error' => $e->getMessage()]);
        }
    });
    
    // Update settings
    Route::middleware('auth:sanctum')->post('/store/{group?}', function (Request $request, $group = 'general') {
        try {
            $data = $request->all();
            
            foreach ($data as $key => $value) {
                if ($key !== '_token') {
                    Settings::store($key, $value, $group);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Upload file (logo, favicon)
    Route::middleware('auth:sanctum')->post('/upload', function (Request $request) {
        try {
            $file = $request->file('logo') ?? $request->file('favicon');
            
            if (!$file) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }
            
            // Store the file
            $path = $file->store('uploads/settings', 'public');
            $url = asset('storage/' . $path);
            
            return response()->json([
                'url' => $url,
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// MODULE USER API (Admin functionality)
// =====================================================
Route::prefix('module/user')->group(function () {
    // Users routes
    Route::middleware('auth:sanctum')->group(function () {
        // Get all users
        Route::get('/users', function (Request $request) {
            try {
                $query = \App\User::query();
                
                if ($request->has('s') && $request->s) {
                    $query->where(function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->s . '%')
                          ->orWhere('email', 'LIKE', '%' . $request->s . '%');
                    });
                }
                
                if ($request->has('status') && $request->status) {
                    $query->where('status', $request->status);
                }
                
                if ($request->has('role_id') && $request->role_id) {
                    $query->where('role_id', $request->role_id);
                }
                
                $users = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);
                
                return response()->json([
                    'data' => $users->items(),
                    'total' => $users->total(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]);
            } catch (\Exception $e) {
                return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
            }
        });
        
        // Get single user
        Route::get('/users/{id}', function ($id) {
            try {
                $user = \App\User::findOrFail($id);
                return response()->json($user);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Create user
        Route::post('/users/store', function (Request $request) {
            try {
                $user = new \App\User();
                $user->name = $request->input('name');
                $user->email = $request->input('email');
                $user->password = bcrypt($request->input('password'));
                $user->role_id = $request->input('role_id', 2);
                $user->status = $request->input('status', 'active');
                $user->save();
                
                return response()->json($user);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Update user
        Route::post('/users/store/{id}', function (Request $request, $id) {
            try {
                $user = \App\User::findOrFail($id);
                $user->name = $request->input('name', $user->name);
                $user->email = $request->input('email', $user->email);
                if ($request->has('password') && $request->password) {
                    $user->password = bcrypt($request->password);
                }
                $user->role_id = $request->input('role_id', $user->role_id);
                $user->status = $request->input('status', $user->status);
                $user->save();
                
                return response()->json($user);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Bulk edit users
        Route::post('/users/bulkEdit', function (Request $request) {
            try {
                $ids = $request->input('ids', []);
                $action = $request->input('action');
                
                if (empty($ids)) {
                    return response()->json(['error' => 'No items selected'], 400);
                }
                
                switch ($action) {
                    case 'delete':
                        \App\User::whereIn('id', $ids)->delete();
                        break;
                    case 'activate':
                        \App\User::whereIn('id', $ids)->update(['status' => 'active']);
                        break;
                    case 'deactivate':
                        \App\User::whereIn('id', $ids)->update(['status' => 'inactive']);
                        break;
                }
                
                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Get all roles
        Route::get('/roles', function () {
            try {
                $roles = \DB::table('roles')->get();
                return response()->json(['data' => $roles]);
            } catch (\Exception $e) {
                return response()->json(['data' => []]);
            }
        });
        
        // Get single role
        Route::get('/roles/{id}', function ($id) {
            try {
                $role = \DB::table('roles')->where('id', $id)->first();
                return response()->json($role);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Create/Update role
        Route::post('/roles/store/{id?}', function (Request $request, $id = null) {
            try {
                $data = [
                    'name' => $request->input('name'),
                    'display_name' => $request->input('display_name'),
                    'description' => $request->input('description'),
                ];
                
                if ($id) {
                    \DB::table('roles')->where('id', $id)->update($data);
                } else {
                    $id = \DB::table('roles')->insertGetId($data);
                }
                
                return response()->json(['id' => $id, 'success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Delete role
        Route::post('/roles/bulkEdit', function (Request $request) {
            try {
                $ids = $request->input('ids', []);
                $action = $request->input('action');
                
                if ($action === 'delete') {
                    // Don't delete admin role (id=1)
                    \DB::table('roles')->whereIn('id', $ids)->where('id', '!=', 1)->delete();
                }
                
                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Get permissions
        Route::get('/permissions', function () {
            try {
                $permissions = \DB::table('permissions')->get();
                
                // Group by module
                $grouped = [];
                foreach ($permissions as $perm) {
                    $module = explode('.', $perm->name)[0] ?? 'general';
                    if (!isset($grouped[$module])) {
                        $grouped[$module] = [];
                    }
                    $grouped[$module][] = $perm;
                }
                
                return response()->json($grouped);
            } catch (\Exception $e) {
                return response()->json([]);
            }
        });
        
        // Assign permissions to role
        Route::post('/roles/{roleId}/permissions', function (Request $request, $roleId) {
            try {
                $permissions = $request->input('permissions', []);
                
                // Clear existing permissions
                \DB::table('permission_role')->where('role_id', $roleId)->delete();
                
                // Add new permissions
                foreach ($permissions as $permId) {
                    \DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
                
                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
    });
});

// =====================================================
// MODULE NEWS API (Admin functionality)
// =====================================================
Route::prefix('module/news')->group(function () {
    // Get all news posts
    Route::get('/', function (Request $request) {
        try {
            $query = \DB::table('bc_news');
            
            if ($request->has('s') && $request->s) {
                $query->where('title', 'LIKE', '%' . $request->s . '%');
            }
            
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('cat_id') && $request->cat_id) {
                $query->where('cat_id', $request->cat_id);
            }
            
            $posts = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);
            
            return response()->json([
                'data' => $posts->items(),
                'total' => $posts->total(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get single post
    Route::get('/edit/{id}', function ($id) {
        try {
            $post = \DB::table('bc_news')->where('id', $id)->first();
            return response()->json(['data' => $post]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Create post
    Route::middleware('auth:sanctum')->post('/store', function (Request $request) {
        try {
            $id = \DB::table('bc_news')->insertGetId([
                'title' => $request->input('title'),
                'slug' => $request->input('slug') ?: \Illuminate\Support\Str::slug($request->input('title')),
                'content' => $request->input('content'),
                'image_id' => $request->input('image_id'),
                'cat_id' => $request->input('cat_id'),
                'status' => $request->input('status', 'publish'),
                'create_user' => $request->user()->id ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return response()->json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update post
    Route::middleware('auth:sanctum')->post('/store/{id}', function (Request $request, $id) {
        try {
            \DB::table('bc_news')->where('id', $id)->update([
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
                'content' => $request->input('content'),
                'image_id' => $request->input('image_id'),
                'cat_id' => $request->input('cat_id'),
                'status' => $request->input('status'),
                'updated_at' => now(),
            ]);
            
            return response()->json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit posts
    Route::middleware('auth:sanctum')->post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            switch ($action) {
                case 'delete':
                    \DB::table('bc_news')->whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    \DB::table('bc_news')->whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    \DB::table('bc_news')->whereIn('id', $ids)->update(['status' => 'draft']);
                    break;
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Generate slug
    Route::post('/generate-slug', function (Request $request) {
        return response()->json([
            'slug' => \Illuminate\Support\Str::slug($request->input('title'))
        ]);
    });
    
    // Categories
    Route::get('/category', function () {
        try {
            $categories = \DB::table('bc_news_category')->get();
            return response()->json(['data' => $categories]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    });
    
    Route::get('/category/edit/{id}', function ($id) {
        try {
            $cat = \DB::table('bc_news_category')->where('id', $id)->first();
            return response()->json($cat);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::middleware('auth:sanctum')->post('/category/store/{id?}', function (Request $request, $id = null) {
        try {
            $data = [
                'name' => $request->input('name'),
                'slug' => $request->input('slug') ?: \Illuminate\Support\Str::slug($request->input('name')),
                'status' => $request->input('status', 'publish'),
            ];
            
            if ($id) {
                \DB::table('bc_news_category')->where('id', $id)->update($data);
            } else {
                $id = \DB::table('bc_news_category')->insertGetId($data);
            }
            
            return response()->json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::middleware('auth:sanctum')->post('/category/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if ($action === 'delete') {
                \DB::table('bc_news_category')->whereIn('id', $ids)->delete();
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Tags
    Route::get('/tag', function () {
        try {
            $tags = \DB::table('bc_news_tag')->get();
            return response()->json(['data' => $tags]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    });
    
    Route::get('/tag/edit/{id}', function ($id) {
        try {
            $tag = \DB::table('bc_news_tag')->where('id', $id)->first();
            return response()->json($tag);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::middleware('auth:sanctum')->post('/tag/store/{id?}', function (Request $request, $id = null) {
        try {
            $data = [
                'name' => $request->input('name'),
                'slug' => $request->input('slug') ?: \Illuminate\Support\Str::slug($request->input('name')),
            ];
            
            if ($id) {
                \DB::table('bc_news_tag')->where('id', $id)->update($data);
            } else {
                $id = \DB::table('bc_news_tag')->insertGetId($data);
            }
            
            return response()->json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::middleware('auth:sanctum')->post('/tag/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if ($action === 'delete') {
                \DB::table('bc_news_tag')->whereIn('id', $ids)->delete();
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// MODULE PAGE API (Admin functionality)
// =====================================================
Route::prefix('module/page')->group(function () {
    // Get all pages
    Route::get('/', function (Request $request) {
        try {
            $query = \DB::table('bc_pages');
            
            if ($request->has('page_name') && $request->page_name) {
                $query->where('title', 'LIKE', '%' . $request->page_name . '%');
            }
            
            if ($request->has('s') && $request->s) {
                $query->where('title', 'LIKE', '%' . $request->s . '%');
            }
            
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            $pages = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);
            
            return response()->json([
                'data' => $pages->items(),
                'total' => $pages->total(),
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get single page
    Route::get('/edit/{id}', function ($id) {
        try {
            $page = \DB::table('bc_pages')->where('id', $id)->first();
            return response()->json(['data' => $page]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get page by slug (public)
    Route::get('/slug/{slug}', function ($slug) {
        try {
            $page = \DB::table('bc_pages')->where('slug', $slug)->where('status', 'publish')->first();
            if (!$page) {
                return response()->json(['error' => 'Page not found'], 404);
            }
            return response()->json($page);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Create page
    Route::middleware('auth:sanctum')->post('/store', function (Request $request) {
        try {
            $id = \DB::table('bc_pages')->insertGetId([
                'title' => $request->input('title'),
                'slug' => $request->input('slug') ?: \Illuminate\Support\Str::slug($request->input('title')),
                'content' => $request->input('content'),
                'image_id' => $request->input('image_id'),
                'template' => $request->input('template', 'default'),
                'status' => $request->input('status', 'publish'),
                'create_user' => $request->user()->id ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return response()->json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update page
    Route::middleware('auth:sanctum')->post('/store/{id}', function (Request $request, $id) {
        try {
            \DB::table('bc_pages')->where('id', $id)->update([
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
                'content' => $request->input('content'),
                'image_id' => $request->input('image_id'),
                'template' => $request->input('template'),
                'status' => $request->input('status'),
                'updated_at' => now(),
            ]);
            
            return response()->json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit pages
    Route::middleware('auth:sanctum')->post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            switch ($action) {
                case 'delete':
                    \DB::table('bc_pages')->whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    \DB::table('bc_pages')->whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    \DB::table('bc_pages')->whereIn('id', $ids)->update(['status' => 'draft']);
                    break;
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Generate slug
    Route::post('/generate-slug', function (Request $request) {
        return response()->json([
            'slug' => \Illuminate\Support\Str::slug($request->input('title'))
        ]);
    });
});

// =====================================================
// PUBLIC NEWS/BLOG API (no auth required)
// =====================================================
Route::prefix('news')->group(function () {
    // Get all published posts
    Route::get('/', function (Request $request) {
        try {
            $query = \DB::table('bc_news')->where('status', 'publish');
            
            // Filter by category
            if ($request->has('cat_id') && $request->cat_id) {
                $query->where('cat_id', $request->cat_id);
            }
            
            // Filter by featured
            if ($request->has('is_featured') && $request->is_featured) {
                $query->where('is_featured', 1);
            }
            
            // Search
            if ($request->has('s') && $request->s) {
                $query->where('title', 'LIKE', '%' . $request->s . '%');
            }
            
            $posts = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 10);
            
            // Transform data with images
            $data = collect($posts->items())->map(function ($post) {
                $post->image_url = $post->image_id ? get_file_url($post->image_id, 'full') : null;
                return $post;
            });
            
            return response()->json([
                'data' => $data,
                'total' => $posts->total(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total_pages' => $posts->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
    });
    
    // Get featured posts
    Route::get('/featured', function (Request $request) {
        try {
            $limit = $request->get('limit', 6);
            $posts = \DB::table('bc_news')
                ->where('status', 'publish')
                ->where('is_featured', 1)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
            
            $data = $posts->map(function ($post) {
                $post->image_url = $post->image_id ? get_file_url($post->image_id, 'full') : null;
                return $post;
            });
            
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    });
    
    // Get public categories
    Route::get('/categories', function () {
        try {
            $categories = \DB::table('bc_news_category')
                ->where('status', 'publish')
                ->orderBy('name')
                ->get();
            return response()->json(['data' => $categories]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    });
    
    // Get all locations for blogs
    Route::get('/all-locations', function () {
        try {
            $locations = Location::where('status', 'publish')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
            return response()->json(['data' => $locations]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    });
    
    // Get single post by slug
    Route::get('/{slug}', function ($slug) {
        try {
            $post = \DB::table('bc_news')
                ->where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$post) {
                return response()->json(['error' => 'Post not found'], 404);
            }
            
            $post->image_url = $post->image_id ? get_file_url($post->image_id, 'full') : null;
            
            // Get category
            if ($post->cat_id) {
                $post->category = \DB::table('bc_news_category')->where('id', $post->cat_id)->first();
            }
            
            // Get related posts
            $related = \DB::table('bc_news')
                ->where('status', 'publish')
                ->where('id', '!=', $post->id)
                ->when($post->cat_id, function ($q) use ($post) {
                    return $q->where('cat_id', $post->cat_id);
                })
                ->orderBy('id', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($p) {
                    $p->image_url = $p->image_id ? get_file_url($p->image_id, 'full') : null;
                    return $p;
                });
            
            $post->related_posts = $related;
            
            return response()->json(['data' => $post]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// PUBLIC PAGES API (no auth required)
// =====================================================
Route::prefix('pages')->group(function () {
    // Get page by slug
    Route::get('/{slug}', function ($slug) {
        try {
            $page = \DB::table('bc_pages')
                ->where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$page) {
                return response()->json(['error' => 'Page not found'], 404);
            }
            
            return response()->json(['data' => $page]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// MODULE MEDIA API (Admin functionality)
// =====================================================
Route::prefix('module/media')->middleware('auth:sanctum')->group(function () {
    // Get media files list
    Route::post('/getLists', [\Modules\Media\Admin\MediaController::class, 'getLists']);
    
    // Upload file
    Route::post('/store', [\Modules\Media\Admin\MediaController::class, 'store']);
    
    // Remove files
    Route::post('/removeFiles', [\Modules\Media\Admin\MediaController::class, 'removeFiles']);
    
    // Update file metadata
    Route::post('/{id}/update', [\Modules\Media\Admin\MediaController::class, 'update']);
    
    // Edit image
    Route::post('/editImage', [\Modules\Media\Admin\MediaController::class, 'editImage']);
    
    // Get single file
    Route::get('/{id}', function ($id) {
        try {
            $file = \Modules\Media\Models\MediaFile::findOrFail($id);
            return response()->json(new \Modules\Media\Resources\MediaResource($file));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// MODULE LANGUAGE TRANSLATIONS API (Admin functionality)
// =====================================================
Route::prefix('module/language/translations')->middleware('auth:sanctum')->group(function () {
    // Get translations for a locale
    Route::get('/{locale}', function ($locale, Request $request) {
        try {
            $langPath = base_path('resources/lang/' . $locale . '.json');
            $translations = [];
            
            if (file_exists($langPath)) {
                $content = file_get_contents($langPath);
                $allTranslations = json_decode($content, true) ?? [];
                
                // Filter and paginate
                $filter = $request->input('filter', 'all');
                $search = $request->input('s', '');
                $page = (int) $request->input('page', 1);
                $perPage = (int) $request->input('per_page', 20);
                
                $filtered = [];
                foreach ($allTranslations as $key => $value) {
                    // Search filter
                    if ($search && stripos($key, $search) === false && stripos($value, $search) === false) {
                        continue;
                    }
                    
                    // Translation status filter
                    $isTranslated = !empty($value) && $value !== $key;
                    if ($filter === 'translated' && !$isTranslated) continue;
                    if ($filter === 'not_translated' && $isTranslated) continue;
                    
                    $filtered[] = [
                        'key' => $key,
                        'original' => $key,
                        'translation' => $value,
                    ];
                }
                
                $total = count($filtered);
                $translations = array_slice($filtered, ($page - 1) * $perPage, $perPage);
                $translated = count(array_filter($allTranslations, fn($v, $k) => !empty($v) && $v !== $k, ARRAY_FILTER_USE_BOTH));
                
                return response()->json([
                    'data' => $translations,
                    'stats' => [
                        'total' => count($allTranslations),
                        'translated' => $translated,
                        'not_translated' => count($allTranslations) - $translated,
                        'progress' => count($allTranslations) > 0 ? round(($translated / count($allTranslations)) * 100) : 0,
                    ],
                    'meta' => [
                        'current_page' => $page,
                        'last_page' => ceil($total / $perPage),
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                ]);
            }
            
            return response()->json([
                'data' => [],
                'stats' => ['total' => 0, 'translated' => 0, 'not_translated' => 0, 'progress' => 0],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Save translations
    Route::post('/{locale}/save', function ($locale, Request $request) {
        try {
            $langPath = base_path('resources/lang/' . $locale . '.json');
            $translations = [];
            
            if (file_exists($langPath)) {
                $content = file_get_contents($langPath);
                $translations = json_decode($content, true) ?? [];
            }
            
            // Update translations
            $newTranslations = $request->input('translations', []);
            foreach ($newTranslations as $item) {
                if (isset($item['key'])) {
                    $translations[$item['key']] = $item['value'] ?? '';
                }
            }
            
            // Save file
            file_put_contents($langPath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return response()->json([
                'success' => true,
                'saved' => count($newTranslations),
                'message' => 'Translations saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Build translations (copy to public folder)
    Route::post('/{locale}/build', function ($locale) {
        try {
            $langPath = base_path('resources/lang/' . $locale . '.json');
            $publicPath = base_path('public/locales/' . $locale . '.json');
            
            if (!file_exists($langPath)) {
                return response()->json(['error' => 'Language file not found'], 404);
            }
            
            // Ensure public directory exists
            $publicDir = dirname($publicPath);
            if (!is_dir($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            
            // Copy file
            copy($langPath, $publicPath);
            
            return response()->json([
                'success' => true,
                'message' => 'Translations built successfully',
                'file' => $publicPath,
                'last_build_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get translation stats
    Route::get('/{locale}/stats', function ($locale) {
        try {
            $langPath = base_path('resources/lang/' . $locale . '.json');
            
            if (!file_exists($langPath)) {
                return response()->json([
                    'total' => 0,
                    'translated' => 0,
                    'not_translated' => 0,
                    'progress' => 0,
                ]);
            }
            
            $content = file_get_contents($langPath);
            $translations = json_decode($content, true) ?? [];
            
            $translated = count(array_filter($translations, fn($v, $k) => !empty($v) && $v !== $k, ARRAY_FILTER_USE_BOTH));
            
            return response()->json([
                'total' => count($translations),
                'translated' => $translated,
                'not_translated' => count($translations) - $translated,
                'progress' => count($translations) > 0 ? round(($translated / count($translations)) * 100) : 0,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// MODULE TOUR BULK EDIT API (Admin)
// =====================================================
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

// =====================================================
// MODULE CONTACT/FORMS API (Admin functionality)
// =====================================================
Route::prefix('module/contact')->group(function () {
    // Public: Submit contact form
    Route::post('/store', function (Request $request) {
        try {
            $id = \DB::table('bc_contact_submissions')->insertGetId([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'form_type' => $request->input('form_type', 'contact'),
                'tour_id' => $request->input('tour_id'),
                'travel_date' => $request->input('travel_date'),
                'number_of_people' => $request->input('number_of_people'),
                'special_requirements' => $request->input('special_requirements'),
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return response()->json(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            // Try bc_contact table if bc_contact_submissions doesn't exist
            try {
                $id = \DB::table('bc_contact')->insertGetId([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                    'subject' => $request->input('subject'),
                    'message' => $request->input('message'),
                    'status' => 'new',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'id' => $id]);
            } catch (\Exception $e2) {
                return response()->json(['error' => $e2->getMessage()], 500);
            }
        }
    });
    
    // Admin routes (protected)
    Route::middleware('auth:sanctum')->group(function () {
        // Get all submissions
        Route::get('/', function (Request $request) {
            try {
                // Try bc_contact_submissions first
                $table = 'bc_contact_submissions';
                if (!\Schema::hasTable($table)) {
                    $table = 'bc_contact';
                }
                
                $query = \DB::table($table);
                
                if ($request->has('search') && $request->search) {
                    $query->where(function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->search . '%')
                          ->orWhere('email', 'LIKE', '%' . $request->search . '%');
                    });
                }
                
                if ($request->has('status') && $request->status !== 'all') {
                    $query->where('status', $request->status);
                }
                
                if ($request->has('form_type') && $request->form_type !== 'all') {
                    $query->where('form_type', $request->form_type);
                }
                
                $submissions = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);
                
                return response()->json([
                    'data' => $submissions->items(),
                    'total' => $submissions->total(),
                    'current_page' => $submissions->currentPage(),
                    'last_page' => $submissions->lastPage(),
                ]);
            } catch (\Exception $e) {
                return response()->json(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
            }
        });
        
        // Get single submission
        Route::get('/{id}', function ($id) {
            try {
                $table = \Schema::hasTable('bc_contact_submissions') ? 'bc_contact_submissions' : 'bc_contact';
                $submission = \DB::table($table)->where('id', $id)->first();
                return response()->json($submission);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Update submission
        Route::post('/{id}', function (Request $request, $id) {
            try {
                $table = \Schema::hasTable('bc_contact_submissions') ? 'bc_contact_submissions' : 'bc_contact';
                
                $data = [];
                if ($request->has('status')) $data['status'] = $request->status;
                if ($request->has('notes')) $data['notes'] = $request->notes;
                $data['updated_at'] = now();
                
                \DB::table($table)->where('id', $id)->update($data);
                
                $submission = \DB::table($table)->where('id', $id)->first();
                return response()->json(['data' => $submission, 'success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Bulk edit
        Route::post('/bulkEdit', function (Request $request) {
            try {
                $table = \Schema::hasTable('bc_contact_submissions') ? 'bc_contact_submissions' : 'bc_contact';
                $ids = $request->input('ids', []);
                $action = $request->input('action');
                
                if (empty($ids)) {
                    return response()->json(['error' => 'No items selected'], 400);
                }
                
                switch ($action) {
                    case 'delete':
                        \DB::table($table)->whereIn('id', $ids)->delete();
                        break;
                    case 'update_status':
                        $status = $request->input('status', 'read');
                        \DB::table($table)->whereIn('id', $ids)->update(['status' => $status]);
                        break;
                }
                
                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Export CSV
        Route::get('/export', function (Request $request) {
            try {
                $table = \Schema::hasTable('bc_contact_submissions') ? 'bc_contact_submissions' : 'bc_contact';
                $submissions = \DB::table($table)->get();
                
                $csv = "ID,Name,Email,Phone,Subject,Message,Status,Created At\n";
                foreach ($submissions as $s) {
                    $csv .= "\"{$s->id}\",\"{$s->name}\",\"{$s->email}\",\"" . ($s->phone ?? '') . "\",\"" . ($s->subject ?? '') . "\",\"" . str_replace('"', '""', $s->message ?? '') . "\",\"{$s->status}\",\"{$s->created_at}\"\n";
                }
                
                return response($csv)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="contacts.csv"');
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
    });
});

// Also register /contact/store for frontend form submission
Route::post('/contact/store', function (Request $request) {
    try {
        // Try bc_contact_submissions first, fallback to bc_contact
        $table = \Schema::hasTable('bc_contact_submissions') ? 'bc_contact_submissions' : 'bc_contact';
        
        $id = \DB::table($table)->insertGetId([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'form_type' => $request->input('form_type', 'contact'),
            'tour_id' => $request->input('tour_id'),
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json(['success' => true, 'id' => $id]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// =====================================================
// MODULE REVIEW API (Admin functionality)
// =====================================================
Route::prefix('module/review')->group(function () {
    // Admin routes (protected)
    Route::middleware('auth:sanctum')->group(function () {
        // Get all reviews
        Route::get('/', function (Request $request) {
            try {
                $query = \DB::table('bc_review');
                
                if ($request->has('search') && $request->search) {
                    $query->where(function ($q) use ($request) {
                        $q->where('title', 'LIKE', '%' . $request->search . '%')
                          ->orWhere('content', 'LIKE', '%' . $request->search . '%')
                          ->orWhere('author_name', 'LIKE', '%' . $request->search . '%');
                    });
                }
                
                if ($request->has('status') && $request->status !== 'all') {
                    $query->where('status', $request->status);
                }
                
                if ($request->has('object_model') && $request->object_model) {
                    $query->where('object_model', $request->object_model);
                }
                
                if ($request->has('object_id') && $request->object_id) {
                    $query->where('object_id', $request->object_id);
                }
                
                if ($request->has('rating') && $request->rating) {
                    $query->where('rate_number', $request->rating);
                }
                
                $reviews = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);
                
                return response()->json([
                    'data' => $reviews->items(),
                    'meta' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json(['data' => [], 'meta' => [], 'error' => $e->getMessage()]);
            }
        });
        
        // Get single review
        Route::get('/edit/{id}', function ($id) {
            try {
                $review = \DB::table('bc_review')->where('id', $id)->first();
                
                if (!$review) {
                    return response()->json(['error' => 'Review not found'], 404);
                }
                
                // Get review meta (category ratings)
                $meta = \DB::table('bc_review_meta')->where('review_id', $id)->get();
                $review->meta = $meta;
                
                return response()->json($review);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Create review
        Route::post('/store', function (Request $request) {
            try {
                $id = \DB::table('bc_review')->insertGetId([
                    'object_id' => $request->input('object_id') ?? $request->input('tour_id'),
                    'object_model' => $request->input('object_model', 'tour'),
                    'author_id' => $request->user()->id ?? null,
                    'title' => $request->input('title'),
                    'content' => $request->input('content'),
                    'rate_number' => $request->input('rating'),
                    'status' => $request->input('status', 'approved'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Save meta if provided
                if ($request->has('meta')) {
                    foreach ($request->meta as $m) {
                        \DB::table('bc_review_meta')->insert([
                            'review_id' => $id,
                            'name' => $m['name'],
                            'val' => $m['val'],
                        ]);
                    }
                }
                
                return response()->json(['success' => true, 'id' => $id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Update review
        Route::post('/store/{id}', function (Request $request, $id) {
            try {
                $data = [
                    'title' => $request->input('title'),
                    'content' => $request->input('content'),
                    'rate_number' => $request->input('rating'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ];
                
                // Only update provided fields
                $data = array_filter($data, fn($v) => $v !== null);
                
                \DB::table('bc_review')->where('id', $id)->update($data);
                
                // Update meta if provided
                if ($request->has('meta')) {
                    \DB::table('bc_review_meta')->where('review_id', $id)->delete();
                    foreach ($request->meta as $m) {
                        \DB::table('bc_review_meta')->insert([
                            'review_id' => $id,
                            'name' => $m['name'],
                            'val' => $m['val'],
                        ]);
                    }
                }
                
                return response()->json(['success' => true, 'id' => $id]);
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
                
                switch ($action) {
                    case 'delete':
                        \DB::table('bc_review')->whereIn('id', $ids)->delete();
                        \DB::table('bc_review_meta')->whereIn('review_id', $ids)->delete();
                        break;
                    case 'approve':
                        \DB::table('bc_review')->whereIn('id', $ids)->update(['status' => 'approved']);
                        break;
                    case 'reject':
                        \DB::table('bc_review')->whereIn('id', $ids)->update(['status' => 'rejected']);
                        break;
                    case 'pending':
                        \DB::table('bc_review')->whereIn('id', $ids)->update(['status' => 'pending']);
                        break;
                }
                
                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Get stats for object
        Route::get('/stats/{objectModel}/{objectId}', function ($objectModel, $objectId) {
            try {
                $reviews = \DB::table('bc_review')
                    ->where('object_model', $objectModel)
                    ->where('object_id', $objectId)
                    ->where('status', 'approved')
                    ->get();
                
                $total = $reviews->count();
                $average = $total > 0 ? round($reviews->avg('rate_number'), 1) : 0;
                
                $counts = [];
                for ($i = 1; $i <= 5; $i++) {
                    $counts[$i] = $reviews->where('rate_number', $i)->count();
                }
                
                return response()->json([
                    'total' => $total,
                    'average' => $average,
                    'counts' => $counts,
                ]);
            } catch (\Exception $e) {
                return response()->json(['total' => 0, 'average' => 0, 'counts' => []]);
            }
        });
    });
    
    // Public: Get approved reviews for object
    Route::get('/public/{objectModel}/{objectId}', function ($objectModel, $objectId) {
        try {
            $reviews = \DB::table('bc_review')
                ->where('object_model', $objectModel)
                ->where('object_id', $objectId)
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            return response()->json(['data' => $reviews]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    });
});

// =====================================================
// MODULE SEO API (Admin functionality)
// =====================================================
Route::prefix('module/core/seo')->middleware('auth:sanctum')->group(function () {
    // Global SEO Settings
    Route::get('/global', function () {
        try {
            $settings = Settings::getSettings('seo');
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    Route::post('/global', function (Request $request) {
        try {
            $data = $request->all();
            foreach ($data as $key => $value) {
                if ($key !== '_token') {
                    Settings::store($key, $value, 'seo');
                }
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // 301 Redirects
    Route::get('/redirects', function (Request $request) {
        try {
            // Check if bc_redirects table exists
            if (!\Schema::hasTable('bc_redirects')) {
                return response()->json(['data' => []]);
            }
            
            $query = \DB::table('bc_redirects');
            
            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('old_url', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('new_url', 'LIKE', '%' . $request->search . '%');
                });
            }
            
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('is_active', $request->status === 'active' ? 1 : 0);
            }
            
            $redirects = $query->orderBy('id', 'desc')->get();
            
            return response()->json(['data' => $redirects]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    });
    
    Route::get('/redirects/{id}', function ($id) {
        try {
            $redirect = \DB::table('bc_redirects')->where('id', $id)->first();
            return response()->json($redirect);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::post('/redirects/store/{id?}', function (Request $request, $id = null) {
        try {
            // Create table if doesn't exist
            if (!\Schema::hasTable('bc_redirects')) {
                \Schema::create('bc_redirects', function ($table) {
                    $table->id();
                    $table->string('old_url');
                    $table->string('new_url');
                    $table->integer('status_code')->default(301);
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                });
            }
            
            $data = [
                'old_url' => $request->input('old_url'),
                'new_url' => $request->input('new_url'),
                'status_code' => $request->input('status_code', 301),
                'is_active' => $request->input('is_active', true),
                'updated_at' => now(),
            ];
            
            if ($id) {
                \DB::table('bc_redirects')->where('id', $id)->update($data);
            } else {
                $data['created_at'] = now();
                $id = \DB::table('bc_redirects')->insertGetId($data);
            }
            
            return response()->json(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::post('/redirects/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if ($action === 'delete') {
                \DB::table('bc_redirects')->whereIn('id', $ids)->delete();
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Sitemap
    Route::get('/sitemap', function () {
        try {
            $settings = Settings::getSettings('sitemap');
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    Route::post('/sitemap', function (Request $request) {
        try {
            $data = $request->all();
            foreach ($data as $key => $value) {
                if ($key !== '_token') {
                    Settings::store('sitemap_' . $key, $value, 'sitemap');
                }
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    Route::post('/sitemap/generate', function () {
        try {
            // Generate basic sitemap
            $sitemapContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $sitemapContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            $baseUrl = config('app.url', 'https://exploreheros.com');
            
            // Add homepage
            $sitemapContent .= "<url><loc>{$baseUrl}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
            
            // Add tours
            $tours = Tour::where('status', 'publish')->get(['slug']);
            foreach ($tours as $tour) {
                $sitemapContent .= "<url><loc>{$baseUrl}/tours/{$tour->slug}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
            }
            
            // Add destinations
            $destinations = Location::where('status', 'publish')->get(['slug']);
            foreach ($destinations as $dest) {
                $sitemapContent .= "<url><loc>{$baseUrl}/destinations/{$dest->slug}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
            }
            
            // Add pages
            $pages = \DB::table('bc_pages')->where('status', 'publish')->get(['slug']);
            foreach ($pages as $page) {
                $sitemapContent .= "<url><loc>{$baseUrl}/{$page->slug}</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>\n";
            }
            
            // Add news/blog
            $posts = \DB::table('bc_news')->where('status', 'publish')->get(['slug']);
            foreach ($posts as $post) {
                $sitemapContent .= "<url><loc>{$baseUrl}/blog/{$post->slug}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
            }
            
            $sitemapContent .= '</urlset>';
            
            // Save sitemap
            $sitemapPath = public_path('sitemap.xml');
            file_put_contents($sitemapPath, $sitemapContent);
            
            return response()->json([
                'success' => true,
                'url' => $baseUrl . '/sitemap.xml',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Robots.txt
    Route::get('/robots', function () {
        try {
            $robotsPath = public_path('robots.txt');
            $content = file_exists($robotsPath) ? file_get_contents($robotsPath) : "User-agent: *\nAllow: /\n\nSitemap: " . config('app.url') . "/sitemap.xml";
            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            return response()->json(['content' => '']);
        }
    });
    
    Route::post('/robots', function (Request $request) {
        try {
            $content = $request->input('content');
            $robotsPath = public_path('robots.txt');
            file_put_contents($robotsPath, $content);
            return response()->json(['content' => $content, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});