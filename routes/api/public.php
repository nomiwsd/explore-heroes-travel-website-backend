<?php

/**
 * PUBLIC API ROUTES
 * These routes are accessible without authentication
 * For website visitors and public content
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\Settings;
use Modules\Language\Models\Language;
use Modules\Core\Models\Menu;
use Modules\Location\Models\Location;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;

// =====================================================
// MENU API
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
// DESTINATIONS API
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
                $query->where('is_featured', 1);
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
                        'tours_count' => Tour::where('location_id', $dest->id)->where('status', 'publish')->count(),
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
            $tours = Tour::where('location_id', $destination->id)
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
// TOUR FILTERS API
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
// TOURS API
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
            
            // Get highlights
            $highlights = [];
            if ($tour->highlight) {
                $highlights = is_string($tour->highlight) ? json_decode($tour->highlight, true) : $tour->highlight;
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
                    'highlights' => $highlights,
                    'meta_title' => $tour->meta_title ?? $tour->title,
                    'meta_description' => $tour->meta_desc ?? $tour->short_desc,
                    'related_tours' => $relatedTours->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'title' => $t->title,
                            'slug' => $t->slug,
                            'price' => $t->price,
                            'sale_price' => $t->sale_price,
                            'duration' => $t->duration,
                            'image_url' => $t->image_id ? get_file_url($t->image_id, 'full') : null,
                            'destination' => $t->location ? [
                                'id' => $t->location->id,
                                'name' => $t->location->name,
                                'slug' => $t->location->slug,
                            ] : null,
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
// SETTINGS API
// =====================================================
Route::prefix('settings')->group(function () {
    Route::get('/{group?}', function ($group = 'general') {
        $settings = Settings::getSettings($group);
        return response()->json($settings);
    });
});

// =====================================================
// TRANSLATIONS API
// =====================================================
Route::prefix('translations')->group(function () {
    // Get all active languages
    Route::get('/languages', function () {
        try {
            $languages = Language::where('status', 'publish')
                ->orderByRaw('CASE WHEN is_default = 1 THEN 0 ELSE 1 END')
                ->get(['id', 'locale', 'name', 'flag', 'is_default', 'status']);

            return response()->json($languages);
        } catch (\Exception $e) {
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

        return response()->json([]);
    });
});

// =====================================================
// REVIEWS API (For Homepage Testimonials)
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

// =====================================================
// NEWS/BLOG API
// =====================================================
Route::prefix('news')->group(function () {
    // Get all published posts
    Route::get('/', function (Request $request) {
        try {
            $query = \Modules\News\Models\News::where('status', 'publish');
            
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
            
            // Transform data with images and author
            $data = $posts->map(function ($post) {
                // Get author info
                $author = null;
                if ($post->create_user) {
                    $user = \App\User::find($post->create_user);
                    if ($user) {
                        $author = [
                            'id' => $user->id,
                            'display_name' => $user->display_name ?? $user->name ?? 'Unknown',
                            'avatar_url' => $user->avatar_id ? get_file_url($user->avatar_id, 'thumb') : null,
                        ];
                    }
                }
                
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                    'status' => $post->status,
                    'is_featured' => $post->is_featured,
                    'cat_id' => $post->cat_id,
                    'image_id' => $post->image_id,
                    'image_url' => $post->image_id ? get_file_url($post->image_id, 'full') : null,
                    'author' => $author,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ];
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
            $posts = \Modules\News\Models\News::where('status', 'publish')
                ->where('is_featured', 1)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
            
            $data = $posts->map(function ($post) {
                // Get author info
                $author = null;
                if ($post->create_user) {
                    $user = \App\User::find($post->create_user);
                    if ($user) {
                        $author = [
                            'id' => $user->id,
                            'display_name' => $user->display_name ?? $user->name ?? 'Unknown',
                            'avatar_url' => $user->avatar_id ? get_file_url($user->avatar_id, 'thumb') : null,
                        ];
                    }
                }
                
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                    'is_featured' => $post->is_featured,
                    'cat_id' => $post->cat_id,
                    'image_id' => $post->image_id,
                    'image_url' => $post->image_id ? get_file_url($post->image_id, 'full') : null,
                    'author' => $author,
                    'created_at' => $post->created_at,
                ];
            });
            
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    });
    
    // Get public categories
    Route::get('/categories', function () {
        try {
            $categories = \Modules\News\Models\NewsCategory::where('status', 'publish')
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
            $post = \Modules\News\Models\News::where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$post) {
                return response()->json(['error' => 'Post not found'], 404);
            }
            
            // Get category
            $category = null;
            if ($post->cat_id) {
                $category = \Modules\News\Models\NewsCategory::find($post->cat_id);
            }
            
            // Get author info
            $author = null;
            if ($post->create_user) {
                $user = \App\User::find($post->create_user);
                if ($user) {
                    $author = [
                        'id' => $user->id,
                        'display_name' => $user->display_name ?? $user->name ?? 'Unknown',
                        'avatar_url' => $user->avatar_id ? get_file_url($user->avatar_id, 'thumb') : null,
                        'bio' => $user->bio ?? null,
                    ];
                }
            }
            
            // Get related posts
            $related = \Modules\News\Models\News::where('status', 'publish')
                ->where('id', '!=', $post->id)
                ->when($post->cat_id, function ($q) use ($post) {
                    return $q->where('cat_id', $post->cat_id);
                })
                ->orderBy('id', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title,
                        'slug' => $p->slug,
                        'excerpt' => $p->excerpt,
                        'image_url' => $p->image_id ? get_file_url($p->image_id, 'full') : null,
                    ];
                });
            
            return response()->json([
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                    'image_url' => $post->image_id ? get_file_url($post->image_id, 'full') : null,
                    'image_alt' => $post->image_alt ?? null,
                    'cat_id' => $post->cat_id,
                    'category' => $category,
                    'author' => $author,
                    'reading_time' => $post->reading_time ?? null,
                    'publish_date' => $post->publish_date ?? null,
                    'created_at' => $post->created_at,
                    'related_posts' => $related,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// PAGES API
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
// CONTACT FORM SUBMISSION (Public)
// =====================================================
Route::post('/contact/store', function (Request $request) {
    try {
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
