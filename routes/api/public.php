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
            // Try multiple query approaches for different MySQL versions
            $menu = Menu::where('status', 'publish')
                ->where(function($q) use ($location) {
                    // Method 1: JSON_CONTAINS (MySQL 5.7+)
                    $q->whereRaw("JSON_CONTAINS(locations, '\"$location\"')")
                      // Method 2: LIKE search for compatibility
                      ->orWhere('locations', 'like', "%\"$location\"%")
                      // Method 3: Direct array value
                      ->orWhere('locations', 'like', "%$location%");
                })
                ->first();
            
            if (!$menu) {
                // Log for debugging
                \Log::info("Menu not found for location: $location");
                return response()->json(null);
            }
            
            // Parse items from JSON string
            $items = $menu->items_json ?? [];
            
            \Log::info("Menu found for location: $location, items count: " . count($items));
            
            return response()->json([
                'id' => $menu->id,
                'name' => $menu->name,
                'items' => $items,
                'locations' => $menu->locations,
            ]);
        } catch (\Exception $e) {
            \Log::error("Menu location error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
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
    
    // Debug: Get all menus with raw data
    Route::get('/debug', function () {
        try {
            $menus = Menu::all();
            return response()->json($menus->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'status' => $menu->status,
                    'locations_raw' => $menu->getAttributes()['locations'] ?? null,
                    'locations_cast' => $menu->locations,
                    'items_count' => count($menu->items_json),
                ];
            }));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
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
            
            // Filter by destination_type
            if ($request->has('type') && !empty($request->type)) {
                $query->where('destination_type', $request->type);
            }
            
            // Filter by is_featured
            if ($request->has('featured') && $request->featured == '1') {
                $query->where('is_featured', 1);
            }
            
            // Filter by show_on_homepage
            if ($request->has('homepage') && $request->homepage == '1') {
                $query->where('show_on_homepage', 1);
            }
            
            $destinations = $query->orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();
            
            return response()->json([
                'data' => $destinations->map(function ($dest) {
                    return [
                        'id' => $dest->id,
                        'name' => $dest->name,
                        'slug' => $dest->slug,
                        'short_description' => $dest->translate()->short_description ?? ($dest->content ? \Illuminate\Support\Str::limit(strip_tags($dest->content), 150) : null),
                        'image_url' => $dest->image_id ? get_file_url($dest->image_id, 'full') : null,
                        'banner_image_url' => $dest->banner_image_id ? get_file_url($dest->banner_image_id, 'full') : null,
                        'map_lat' => $dest->map_lat,
                        'map_lng' => $dest->map_lng,
                        'is_featured' => $dest->is_featured,
                        'show_on_homepage' => $dest->show_on_homepage,
                        'destination_type' => $dest->destination_type,
                        'display_order' => $dest->display_order,
                        'tours_count' => \Modules\Tour\Models\TourLocation::where('location_id', $dest->id)->count() ?: Tour::where('location_id', $dest->id)->where('status', 'publish')->count(),
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
            
            $translation = $destination->translate();
            $seo = $destination->getSeoMeta();

            // Get assigned tours via pivot table
            $assignedTourIds = \Modules\Tour\Models\TourLocation::where('location_id', $destination->id)->pluck('tour_id');
            
            if ($assignedTourIds->count() > 0) {
                $tours = Tour::whereIn('id', $assignedTourIds)
                    ->where('status', 'publish')
                    ->get();
            } else {
                // Fallback to old location_id column
                $tours = Tour::where('location_id', $destination->id)
                    ->where('status', 'publish')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            // Parse gallery
            $gallery = [];
            if ($destination->gallery) {
                $galleryIds = is_array($destination->gallery) ? $destination->gallery : json_decode($destination->gallery, true);
                if (is_array($galleryIds)) {
                    foreach ($galleryIds as $id) {
                        $url = get_file_url($id, 'full');
                        if ($url) $gallery[] = $url;
                    }
                }
            }
            
            return response()->json([
                'data' => [
                    'id' => $destination->id,
                    'name' => $translation->name ?? $destination->name,
                    'slug' => $destination->slug,
                    'short_description' => $translation->short_description,
                    'content' => $translation->content,
                    'image_url' => $destination->image_id ? get_file_url($destination->image_id, 'full') : null,
                    'banner_image_url' => $destination->banner_image_id ? get_file_url($destination->banner_image_id, 'full') : null,
                    'gallery_images' => $gallery,
                    'destination_type' => $destination->destination_type,
                    'map_lat' => $destination->map_lat,
                    'map_lng' => $destination->map_lng,
                    'map_zoom' => $destination->map_zoom,
                    'meta_title' => $seo['seo_title'] ?? $destination->name,
                    'meta_description' => $seo['seo_desc'] ?? $translation->short_description,
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
    // Get all published tours
    Route::get('/', function (Request $request) {
        try {
            $query = Tour::where('status', 'publish');
            
            // Filter by destination
            if ($request->has('destination_id') && $request->destination_id) {
                $destIds = is_array($request->destination_id) ? $request->destination_id : explode(',', $request->destination_id);
                $query->whereIn('location_id', $destIds);
            }
            
            // Filter by category (Multi-category support)
            if ($request->has('category_id') && $request->category_id) {
                $catIds = is_array($request->category_id) ? $request->category_id : explode(',', $request->category_id);
                 $query->where(function($q) use ($catIds) {
                     foreach($catIds as $catId) {
                         $id = (int)$catId;
                         // Check either JSON contains exact ID (integer or string representation)
                         // OR legacy handling or partial string match
                         $q->orWhereRaw("JSON_CONTAINS(category_ids, '$id')")
                           ->orWhere('category_ids', 'LIKE', "%\"$id\"%")
                           ->orWhere('category_ids', 'LIKE', "%$id%"); 
                     }
                 });
            }
            
            // Filter by tour_type
            if ($request->has('tour_type') && $request->tour_type) {
                if (is_array($request->tour_type)) {
                     $query->whereIn('tour_type', $request->tour_type);
                } else {
                     $query->where('tour_type', $request->tour_type);
                }
            }

            // Filter by themes (terms)
            if ($request->has('terms') && $request->terms) {
                $terms = is_array($request->terms) ? $request->terms : explode(',', $request->terms);
                $query->where(function($q) use ($terms) {
                    foreach($terms as $term) {
                        $termId = (int)$term;
                         $q->orWhereRaw("JSON_CONTAINS(tour_themes, '$termId')")
                           ->orWhere('tour_themes', 'LIKE', "%\"$termId\"%");
                    }
                });
            }

            // Filter by duration (Days Range)
            if ($request->has('duration_min') || $request->has('duration_max')) {
                 if ($request->has('duration_min')) {
                     $query->where('duration', '>=', $request->duration_min);
                 }
                 if ($request->has('duration_max')) {
                     $query->where('duration', '<=', $request->duration_max);
                 }
            }

            // Filter by price
            if ($request->has('price_range') && $request->price_range) {
                $parts = explode(';', $request->price_range); // 0;1000
                if (count($parts) === 2) {
                    $min = (float)$parts[0];
                    $max = (float)$parts[1];
                     $query->where(function($q) use ($min, $max) {
                         $q->whereBetween('price', [$min, $max])
                           ->orWhereBetween('sale_price', [$min, $max]);
                     });
                }
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
                    // Fetch categories
                    $categories = collect([]);
                     if (!empty($tour->category_ids)) {
                         $catIds = is_string($tour->category_ids) ? json_decode($tour->category_ids, true) : $tour->category_ids;
                         if (is_array($catIds) && count($catIds) > 0) {
                             $categories = \Modules\Tour\Models\TourCategory::whereIn('id', $catIds)->select('id', 'name', 'slug')->get();
                         }
                     }

                     // Fetch themes
                     $themes = collect([]);
                     if (!empty($tour->tour_themes)) {
                         $themeIds = is_string($tour->tour_themes) ? json_decode($tour->tour_themes, true) : $tour->tour_themes;
                         if (is_array($themeIds) && count($themeIds) > 0) {
                             $themes = \DB::table('bc_terms')->whereIn('id', $themeIds)->select('id', 'name', 'slug', 'icon')->get();
                         }
                     }

                    return [
                        'id' => $tour->id,
                        'title' => $tour->title,
                        'slug' => $tour->slug,
                        'short_description' => $tour->short_desc,
                        'price' => $tour->price,
                        'sale_price' => $tour->sale_price,
                        'duration' => $tour->duration,
                        'nights' => $tour->nights, // Updated to use nights column
                        'duration_nights' => $tour->nights, // Legacy support
                        'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                        'banner_url' => $tour->banner_image_url ? $tour->banner_image_url : ($tour->banner_image_id ? get_file_url($tour->banner_image_id, 'full') : null),
                        'is_featured' => $tour->is_featured,
                        'tour_type' => $tour->tour_type,
                        'pricing_type' => $tour->pricing_type,
                        'min_people' => $tour->min_people,
                        'max_people' => $tour->max_people,
                        'destination' => $tour->location ? [
                            'id' => $tour->location->id,
                            'name' => $tour->location->name,
                            'slug' => $tour->location->slug,
                        ] : null,
                        'categories' => $categories,
                        'tour_themes' => $themes,
                        // Legacy single category for compatibility (optional)
                        'category' => $categories->first() ?? null, 
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
            
            // Get highlights (Prioritize new column)
            $highlights = [];
            if ($tour->highlights && count($tour->highlights) > 0) {
                $highlights = $tour->highlights;
            } elseif ($tour->highlight) {
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
            
            // Get gallery images (Robust ID/Path handling)
            $gallery = [];
            if ($tour->gallery) {
                $galleryIds = is_string($tour->gallery) ? json_decode($tour->gallery, true) : $tour->gallery;
                if (is_array($galleryIds)) {
                    $gallery = array_map(function($item) {
                        if (is_numeric($item)) {
                            return get_file_url($item, 'full');
                        }
                        return $item; // Assume it is already a URL or path
                    }, $galleryIds);
                }
            }

            // Hero Slider
            $hero_slider = [];
            if ($tour->hero_slider) {
                 $heroIds = is_string($tour->hero_slider) ? json_decode($tour->hero_slider, true) : $tour->hero_slider;
                 if (is_array($heroIds)) {
                     $hero_slider = array_map(function($item) {
                         if (is_numeric($item)) {
                             return get_file_url($item, 'full');
                         }
                         return $item; // Assume it is already a URL or path
                     }, $heroIds);
                 }
            }
            
            // Get related tours (Updated with new fields)
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
                    'price' => $tour->price,
                    'sale_price' => $tour->sale_price,
                    'duration' => $tour->duration,
                    'nights' => $tour->nights, // Updated
                    'duration_nights' => $tour->nights,
                    'group_size' => $tour->max_people,
                    'min_people' => $tour->min_people,
                    'tour_type' => $tour->tour_type,
                    'pricing_type' => $tour->pricing_type,
                    'image_url' => $tour->image_id ? get_file_url($tour->image_id, 'full') : null,
                    'banner_url' => $tour->banner_image_url ? $tour->banner_image_url : ($tour->banner_image_id ? get_file_url($tour->banner_image_id, 'full') : null),
                    'map_lat' => $tour->map_lat,
                    'map_lng' => $tour->map_lng,
                    'map_embed' => $tour->map_embed,
                    'is_featured' => $tour->is_featured,
                    'destination' => $tour->location ? [
                        'id' => $tour->location->id,
                        'name' => $tour->location->name,
                        'slug' => $tour->location->slug,
                    ] : null,
                    'category' => $tour->category ? [
                        'id' => $tour->category->id,
                        'name' => $tour->category->name,
                        'slug' => $tour->category->slug,
                    ] : null,
                    'gallery' => $gallery,
                    'hero_slider' => $hero_slider,
                    'itinerary' => $itinerary,
                    'include' => $include, // inclusions (structured)
                    'exclude' => $exclude, // exclusions (structured)
                    'inclusions' => $tour->inclusions ?? $include, // arrays
                    'exclusions' => $tour->exclusions ?? $exclude, // arrays
                    'highlights' => $highlights,
                    'faqs' => $faqs,
                    'tour_themes' => collect($tour->tour_themes ?? [])->map(function($id) {
                        $term = \DB::table('bc_terms')->where('id', $id)->first();
                        return $term ? ['id' => $term->id, 'name' => $term->name, 'icon' => $term->icon] : ['id' => $id, 'name' => "Theme $id"];
                    })->toArray(),
                    'suitable_for' => $tour->suitable_for,
                    'cities_covered' => $tour->cities_covered,
                    'availability_dates' => $tour->availability_dates,
                    'conditions' => $tour->conditions,
                    'cancellation_policy' => $tour->cancellation_policy,
                    'child_policy' => $tour->child_policy,
                    'payment_terms' => $tour->payment_terms,
                    'meta_title' => $tour->seo_title,
                    'meta_description' => $tour->seo_desc,
                    'og_title' => $tour->og_title,
                    'og_description' => $tour->og_description,
                    'og_image' => $tour->og_image,
                    'twitter_title' => $tour->twitter_title,
                    'twitter_description' => $tour->twitter_description,
                    'twitter_image' => $tour->twitter_image,
                    'twitter_card' => $tour->twitter_card,
                    // Missing fields added
                    'address' => $tour->address,
                    'map_zoom' => $tour->map_zoom,
                    'map_image_url' => $tour->map_image_id ? get_file_url($tour->map_image_id, 'full') : null,
                    'start_date' => $tour->start_date,
                    'end_date' => $tour->end_date,
                    'enable_fixed_date' => $tour->enable_fixed_date,
                    'min_day_before_booking' => $tour->min_day_before_booking,
                    'review_score' => $tour->review_score,
                    'tour_expert' => $tour->tourExpert ? [
                        'id' => (int) $tour->tourExpert->id,
                        'name' => $tour->tourExpert->getDisplayName() ?? $tour->tourExpert->name ?? $tour->tourExpert->first_name,
                        'email' => $tour->tourExpert->email ?? '',
                        'avatar' => $tour->tourExpert->avatar_id ? get_file_url($tour->tourExpert->avatar_id, 'thumb') : null,
                    ] : null,
                    'author' => $tour->author ? [
                        'id' => $tour->author->id,
                        'name' => $tour->author->getDisplayName() ?? $tour->author->name ?? $tour->author->first_name,
                         'avatar_url' => $tour->author->getAvatarUrl() ?? null,
                    ] : null,
                    'created_at' => $tour->created_at,
                    'updated_at' => $tour->updated_at,
                    'related_tours' => $relatedTours->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'title' => $t->title,
                            'slug' => $t->slug,
                            'price' => $t->price,
                            'sale_price' => $t->sale_price,
                            'duration' => $t->duration,
                            'nights' => $t->nights,
                            'tour_type' => $t->tour_type,
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

    // Submit a public review
    Route::post('/submit', function (Request $request) {
        try {
            // Allow either tour_id OR object_id/object_model
            $rules = [
                'title' => 'required',
                'content' => 'required',
                'rating' => 'required|numeric|min:1|max:5',
            ];
            
            // If tour_id is provided, use it; otherwise require object_id and object_model
            if ($request->has('tour_id') && $request->input('tour_id')) {
                $rules['tour_id'] = 'required|numeric';
            } else {
                $rules['object_id'] = 'required';
                $rules['object_model'] = 'required';
            }
            
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            // Determine object_id and object_model from tour_id if provided
            $objectId = $request->input('object_id');
            $objectModel = $request->input('object_model', 'tour');
            
            if ($request->has('tour_id') && $request->input('tour_id')) {
                $objectId = $request->input('tour_id');
                $objectModel = 'tour';
            }

            $review = new \Modules\Review\Models\Review([
                'object_id' => $objectId,
                'object_model' => $objectModel,
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'rate_number' => $request->input('rating'),
                'author_ip' => $request->ip(),
                'status' => 'pending',
                'author_id' => auth('sanctum')->id() ?? null,
            ]);

            $review->save();

            if ($request->has('author_name')) $review->addMeta('author_name', $request->input('author_name'));
            if ($request->has('author_email')) $review->addMeta('author_email', $request->input('author_email'));
            if ($request->has('author_location')) $review->addMeta('author_location', $request->input('author_location'));
            
            // Always save review_source and review_date with defaults
            $review->addMeta('review_source', $request->input('review_source', 'website'));
            $review->addMeta('review_date', $request->input('review_date', date('Y-m-d')));
            
            // Handle dynamic meta array
            if ($request->has('meta') && is_array($request->input('meta'))) {
                foreach ($request->input('meta') as $metaItem) {
                    if (isset($metaItem['name']) && isset($metaItem['val'])) {
                        $review->addMeta($metaItem['name'], $metaItem['val']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
                            'avatar_url' => null,
                        ];
                        if ($user->avatar_id) {
                            $media = \Modules\Media\Models\MediaFile::find($user->avatar_id);
                            if ($media) {
                                $author['avatar_url'] = '/uploads/' . ltrim($media->file_path, '/');
                                $author['avatar_url'] = str_replace('/uploads/uploads/', '/uploads/', $author['avatar_url']);
                            }
                        }
                    }
                }
                
                // Clean image URL (return relative path for frontend robustness)
                $imageUrl = null;
                if ($post->image_id) {
                    $media = \Modules\Media\Models\MediaFile::find($post->image_id);
                    if ($media) {
                        $imageUrl = '/uploads/' . ltrim($media->file_path, '/');
                        $imageUrl = str_replace('/uploads/uploads/', '/uploads/', $imageUrl);
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
                    'image_url' => $imageUrl,
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
                            'avatar_url' => null,
                        ];
                        if ($user->avatar_id) {
                            $media = \Modules\Media\Models\MediaFile::find($user->avatar_id);
                            if ($media) {
                                $author['avatar_url'] = '/uploads/' . ltrim($media->file_path, '/');
                                $author['avatar_url'] = str_replace('/uploads/uploads/', '/uploads/', $author['avatar_url']);
                            }
                        }
                    }
                }
                
                // Clean image URL (return relative path for frontend robustness)
                $imageUrl = null;
                if ($post->image_id) {
                    $media = \Modules\Media\Models\MediaFile::find($post->image_id);
                    if ($media) {
                        $imageUrl = '/uploads/' . ltrim($media->file_path, '/');
                        $imageUrl = str_replace('/uploads/uploads/', '/uploads/', $imageUrl);
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
                    'image_url' => $imageUrl,
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
                ->with(['location']) // Eager load location
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
                        'avatar_url' => null,
                        'bio' => $user->bio ?? null,
                    ];
                    if ($user->avatar_id) {
                        $media = \Modules\Media\Models\MediaFile::find($user->avatar_id);
                        if ($media) {
                            $author['avatar_url'] = '/uploads/' . ltrim($media->file_path, '/');
                            $author['avatar_url'] = str_replace('/uploads/uploads/', '/uploads/', $author['avatar_url']);
                        }
                    }
                }
            }
            
            // Clean image URL helper (return relative path for frontend robustness)
            $getImageUrl = function($imageId) {
                if (!$imageId) return null;
                $media = \Modules\Media\Models\MediaFile::find($imageId);
                if (!$media) return null;
                $path = '/uploads/' . ltrim($media->file_path, '/');
                return str_replace('/uploads/uploads/', '/uploads/', $path);
            };
            
            // Get related posts
            $related = \Modules\News\Models\News::where('status', 'publish')
                ->where('id', '!=', $post->id)
                ->when($post->cat_id, function ($q) use ($post) {
                    return $q->where('cat_id', $post->cat_id);
                })
                ->orderBy('id', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($p) use ($getImageUrl) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title,
                        'slug' => $p->slug,
                        'excerpt' => $p->excerpt,
                        'image_url' => $getImageUrl($p->image_id),
                        'created_at' => $p->created_at,
                        'publish_date' => $p->created_at ? $p->created_at->format('Y-m-d') : null,
                    ];
                });
            
            return response()->json([
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                    'image_url' => $getImageUrl($post->image_id),
                    'image_alt' => $post->image_alt ?? null,
                    'cat_id' => $post->cat_id,
                    'category' => $category,
                    'location' => $post->location ? [
                        'id' => $post->location->id,
                        'name' => $post->location->name,
                        'slug' => $post->location->slug,
                    ] : null,
                    'author' => $author,
                    'reading_time' => $post->reading_time ?? null,
                    'publish_date' => $post->created_at ? $post->created_at->format('Y-m-d') : null,
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
    // Get pages for menu (header/footer)
    Route::get('/menu', function (Request $request) {
        try {
            $location = $request->input('location', 'menu'); // menu, header, footer
            
            $query = \Modules\Page\Models\Page::where('status', 'publish');
            
            if ($location === 'header') {
                $query->where('show_in_header', true);
            } elseif ($location === 'footer') {
                $query->where('show_in_footer', true);
            } else {
                $query->where('show_in_menu', true);
            }
            
            $pages = $query->orderBy('display_order', 'asc')->get();
            
            return response()->json([
                'data' => $pages->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'title' => $page->title,
                        'slug' => $page->slug,
                        'url' => $page->slug === 'home' ? '/' : '/' . $page->slug,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    });
    
    // Get homepage
    Route::get('/homepage', function () {
        try {
            $page = \Modules\Page\Models\Page::where('status', 'publish')
                ->where('is_homepage', true)
                ->first();
            
            if (!$page) {
                // Fallback to slug 'home' or first page
                $page = \Modules\Page\Models\Page::where('status', 'publish')
                    ->where('slug', 'home')
                    ->first();
            }
            
            return response()->json($page);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get page by slug (must be last due to wildcard)
    Route::get('/{slug}', function ($slug) {
        try {
            $page = \Modules\Page\Models\Page::where('slug', $slug)
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
            'destination_name' => $request->input('destination_name'),
            'travel_date' => $request->input('travel_date'),
            'number_of_people' => $request->input('number_of_people'),
            'special_requirements' => $request->input('special_requirements'),
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
// PAGE SETTINGS API (For Dynamic Page Content)
// =====================================================
Route::prefix('page-settings')->group(function () {
    // Get page settings by slug (public - for frontend rendering)
    Route::get('/{slug}', [\App\Http\Controllers\Api\PageSettingsController::class, 'show']);
    
    // Validate preview token
    Route::get('/{slug}/validate-preview', [\App\Http\Controllers\Api\PageSettingsController::class, 'validatePreviewToken']);
});

// Admin routes for page settings (protected)
Route::prefix('admin/page-settings')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PageSettingsController::class, 'index']);
    Route::put('/{slug}', [\App\Http\Controllers\Api\PageSettingsController::class, 'update']);
    Route::post('/{slug}/publish', [\App\Http\Controllers\Api\PageSettingsController::class, 'publish']);
    Route::get('/{slug}/preview-token', [\App\Http\Controllers\Api\PageSettingsController::class, 'getPreviewToken']);
});

