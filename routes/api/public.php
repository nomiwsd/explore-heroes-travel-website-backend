<?php

/**
 * PUBLIC API ROUTES
 * These routes are accessible without authentication
 * For website visitors and public content
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                Log::info("Menu not found for location: $location");
                return response()->json(null);
            }
            
            // Parse items from JSON string
            $items = $menu->items_json ?? [];

            Log::info("Menu found for location: $location, items count: " . count($items));
            
            return response()->json([
                'id' => $menu->id,
                'name' => $menu->name,
                'items' => $items,
                'locations' => $menu->locations,
            ]);
        } catch (\Exception $e) {
            Log::error("Menu location error: " . $e->getMessage());
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

            $lang = $request->query('lang');
            $destinations = $query->orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();
            
            return response()->json([
                'data' => $destinations->map(function ($dest) use ($lang) {
                    $translation = $lang ? $dest->translate($lang) : null;
                    return [
                        'id' => $dest->id,
                        'name' => $translation->name ?? $dest->name,
                        'slug' => $dest->slug,
                        'short_description' => $translation->short_description ?? ($dest->translate()->short_description ?? ($dest->content ? \Illuminate\Support\Str::limit(strip_tags($dest->content), 150) : null)),
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
    Route::get('/{slug}', function (Request $request, $slug) {
        try {
            $destination = Location::where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$destination) {
                return response()->json(['error' => 'Destination not found'], 404);
            }

            $lang = $request->query('lang');
            $translation = $destination->translate($lang);

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
                    'short_description' => $translation->short_description ?? $destination->short_description,
                    'content' => $translation->content ?? $destination->content,
                    'image_url' => $destination->image_id ? get_file_url($destination->image_id, 'full') : null,
                    'banner_image_url' => $destination->banner_image_id ? get_file_url($destination->banner_image_id, 'full') : null,
                    'gallery_images' => $gallery,
                    'destination_type' => $destination->destination_type,
                    'map_lat' => $destination->map_lat,
                    'map_lng' => $destination->map_lng,
                    'map_zoom' => $destination->map_zoom,
                    'meta_title' => $translation->name ?? $seo['seo_title'] ?? $destination->name,
                    'meta_description' => $translation->short_description ?? $seo['seo_desc'],
                    'og_title' => $destination->og_title,
                    'og_description' => $destination->og_description,
                    'og_image_url' => $destination->og_image_id ? get_file_url($destination->og_image_id, 'full') : null,
                    'twitter_title' => $destination->twitter_title,
                    'twitter_description' => $destination->twitter_description,
                    'twitter_image_url' => $destination->twitter_image_id ? get_file_url($destination->twitter_image_id, 'full') : null,
                    'twitter_card' => $destination->twitter_card,
                    'canonical_url' => $destination->canonical_url,
                    'robots_meta' => $destination->robots_meta,
                    'schema_markup' => $destination->schema_markup,
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
        $categories = DB::table('bc_tour_category')
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
        $travelStylesAttr = DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'travel-styles')
            ->first();
        
        if (!$travelStylesAttr) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $themes = DB::table('bc_terms')
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
        $facilitiesAttr = DB::table('bc_attrs')
            ->where('service', 'tour')
            ->where('slug', 'facilities')
            ->first();
        
        if (!$facilitiesAttr) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $facilities = DB::table('bc_terms')
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
            $lang = $request->query('lang');
            $tours = $query->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            return response()->json([
                'data' => $tours->map(function ($tour) use ($lang) {
                    $translation = $lang ? $tour->translate($lang) : null;
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
                            $themes = DB::table('bc_terms')->whereIn('id', $themeIds)->select('id', 'name', 'slug', 'icon')->get();
                         }
                     }

                    return [
                        'id' => $tour->id,
                        'title' => $translation->title ?? $tour->title,
                        'slug' => $tour->slug,
                        'short_description' => $translation->short_desc ?? $tour->short_desc,
                        'price' => $tour->price,
                        'sale_price' => $tour->sale_price,
                        'duration' => $tour->duration,
                        'duration_type' => $tour->duration_type,
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
    Route::get('/{slug}', function (Request $request, $slug) {
        try {
            $tour = Tour::where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$tour) {
                return response()->json(['error' => 'Tour not found'], 404);
            }

            // Get translation if lang parameter is provided
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $tour->translate($lang);
            }

            // Get translatable fields (use translation if available, else main tour)
            $title = ($translation && $translation->title) ? $translation->title : $tour->title;
            $shortDesc = ($translation && $translation->short_desc) ? $translation->short_desc : $tour->short_desc;
            $address = ($translation && $translation->address) ? $translation->address : $tour->address;
            $conditions = ($translation && $translation->conditions) ? $translation->conditions : $tour->conditions;
            $cancellationPolicy = ($translation && $translation->cancellation_policy) ? $translation->cancellation_policy : $tour->cancellation_policy;
            $childPolicy = ($translation && $translation->child_policy) ? $translation->child_policy : $tour->child_policy;
            $paymentTerms = ($translation && $translation->payment_terms) ? $translation->payment_terms : $tour->payment_terms;

            // Get itinerary (check translation first)
            $itinerary = [];
            $itinerarySrc = ($translation && $translation->itinerary) ? $translation->itinerary : $tour->itinerary;
            if ($itinerarySrc) {
                $itinerary = is_string($itinerarySrc) ? json_decode($itinerarySrc, true) : $itinerarySrc;
            }

            // Get FAQs (check translation first)
            $faqs = [];
            $faqsSrc = ($translation && $translation->faqs) ? $translation->faqs : $tour->faqs;
            if ($faqsSrc) {
                $faqs = is_string($faqsSrc) ? json_decode($faqsSrc, true) : $faqsSrc;
            }
            
            // Get highlights (Prioritize new column)
            $highlights = [];
            if (!empty($tour->highlights)) {
                 $highlights = is_string($tour->highlights) ? json_decode($tour->highlights, true) : $tour->highlights;
            } elseif ($tour->highlight) {
                $highlights = is_string($tour->highlight) ? json_decode($tour->highlight, true) : $tour->highlight;
            }

            // Get include/exclude (check translation first)
            $include = [];
            $exclude = [];
            $inclusionsSrc = ($translation && $translation->inclusions) ? $translation->inclusions : ($tour->inclusions ?? $tour->include);
            $exclusionsSrc = ($translation && $translation->exclusions) ? $translation->exclusions : ($tour->exclusions ?? $tour->exclude);
            if ($inclusionsSrc) {
                $include = is_string($inclusionsSrc) ? json_decode($inclusionsSrc, true) : $inclusionsSrc;
            }
            if ($exclusionsSrc) {
                $exclude = is_string($exclusionsSrc) ? json_decode($exclusionsSrc, true) : $exclusionsSrc;
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
            
            // Fetch categories (Hydrate full objects from IDs)
            $categories = collect([]);
            if (!empty($tour->category_ids)) {
                $catIds = is_string($tour->category_ids) ? json_decode($tour->category_ids, true) : $tour->category_ids;
                if (is_array($catIds) && count($catIds) > 0) {
                    $categories = \Modules\Tour\Models\TourCategory::whereIn('id', $catIds)->select('id', 'name', 'slug')->get();
                }
            }

            // Prepare Tour Themes IDs for hydration
            $tourThemesIds = [];
            if (!empty($tour->tour_themes)) {
                $tourThemesIds = is_string($tour->tour_themes) ? json_decode($tour->tour_themes, true) : $tour->tour_themes;
            }

            // Get related tours (Prioritize manual related_tour_ids)
            $relatedTourIds = [];
            if (!empty($tour->related_tour_ids)) {
                $relatedTourIds = is_string($tour->related_tour_ids) ? json_decode($tour->related_tour_ids, true) : $tour->related_tour_ids;
            }

            $relatedToursQuery = Tour::where('status', 'publish')->where('id', '!=', $tour->id);
            
            if (!empty($relatedTourIds) && is_array($relatedTourIds) && count($relatedTourIds) > 0) {
                $relatedToursQuery->whereIn('id', $relatedTourIds);
            } else {
                $relatedToursQuery->where(function($q) use ($tour) {
                    $q->where('location_id', $tour->location_id);
                      // ->orWhere('category_id', $tour->category_id); // Deprecated single category check
                });
            }
            
            $relatedTours = $relatedToursQuery->limit(4)->get();
            
            return response()->json([
                'data' => [
                    'id' => $tour->id,
                    'title' => $title,
                    'slug' => $tour->slug,
                    'short_description' => $shortDesc,
                    'price' => $tour->price,
                    'sale_price' => $tour->sale_price,
                    'duration' => $tour->duration,
                    'duration_type' => $tour->duration_type,

                    'nights' => $tour->nights, // Frontend uses nights or duration (frontend interface has duration_nights but mapped to nights)
                    'duration_nights' => $tour->nights, // Added as per user request
                    // duration_nights removed (redundant)
                    'group_size' => $tour->max_people,
                    'min_people' => $tour->min_people,
                    'tour_type' => $tour->tour_type,
                    'pricing_type' => $tour->pricing_type,
                    'image_url' => $tour->image_id ? parse_url(get_file_url($tour->image_id, 'full'), PHP_URL_PATH) : null,
                    'banner_url' => $tour->banner_image_url ? parse_url($tour->banner_image_url, PHP_URL_PATH) : ($tour->banner_image_id ? parse_url(get_file_url($tour->banner_image_id, 'full'), PHP_URL_PATH) : null),
                    'map_lat' => $tour->map_lat,
                    'map_lng' => $tour->map_lng,
                    'map_zoom' => $tour->map_zoom,
                    // map_embed removed
                    'map_image_url' => $tour->map_image_id ? parse_url(get_file_url($tour->map_image_id, 'full'), PHP_URL_PATH) : null,
                    'is_featured' => $tour->is_featured,
                    'destination' => $tour->location ? [
                        'id' => $tour->location->id,
                        'name' => $tour->location->name,
                        'slug' => $tour->location->slug,
                        'image_url' => $tour->location->image_id ? parse_url(get_file_url($tour->location->image_id, 'full'), PHP_URL_PATH) : null,
                    ] : null,
                    'categories' => $categories, 
                    // gallery removed
                    'hero_slider' => $hero_slider,
                    'itinerary' => $itinerary,
                    // include/exclude removed (raw), use inclusions/exclusions
                    'inclusions' => $include,
                    'exclusions' => $exclude,
                    'highlights' => $highlights,
                    'faqs' => $faqs,
                    'tour_themes' => collect($tourThemesIds ?? [])->map(function($id) {
                        $term = DB::table('bc_terms')->where('id', $id)->first();
                        return $term ? ['id' => $term->id, 'name' => $term->name, 'icon' => $term->icon, 'slug' => $term->slug] : ['id' => $id, 'name' => "Theme $id"];
                    })->toArray(),
                    'suitable_for' => $tour->suitable_for,
                    'cities_covered' => $tour->cities_covered,
                    'availability_dates' => $tour->availability_dates,
                    'conditions' => $conditions,
                    'cancellation_policy' => $cancellationPolicy,
                    'child_policy' => $childPolicy,
                    'payment_terms' => $paymentTerms,
                    'meta_title' => $tour->seo_title,
                    'meta_description' => $tour->seo_desc,
                    // OG/Twitter fields kept if defined in interface or likely needed for SEO Head
                    'og_title' => $tour->og_title,
                    'og_description' => $tour->og_description,
                    'og_image_url' => $tour->og_image_id ? parse_url(get_file_url($tour->og_image_id, 'full'), PHP_URL_PATH) : null,
                    'twitter_title' => $tour->twitter_title,
                    'twitter_description' => $tour->twitter_description,
                    'twitter_image_url' => $tour->twitter_image_id ? get_file_url($tour->twitter_image_id, 'full') : null,
                    'twitter_card' => $tour->twitter_card,
                    'canonical_url' => $tour->canonical_url,
                    'robots_meta' => $tour->robots_meta,
                    'schema_markup' => $tour->schema_markup,
                    'address' => $address,
                    // start_date, end_date, enable_fixed_date, min_day_before_booking removed (not in PublicTourDetail shown or assumed extra)
                    'review_score' => $tour->review_score,
                    'tour_expert' => $tour->tourExpert ? [
                        'id' => (int) $tour->tourExpert->id,
                        'name' => $tour->tourExpert->getDisplayName() ?? $tour->tourExpert->name ?? $tour->tourExpert->first_name,
                        'email' => $tour->tourExpert->email ?? '',
                        'avatar' => $tour->tourExpert->avatar_id ? get_file_url($tour->tourExpert->avatar_id, 'thumb') : null,
                    ] : null,
                    // author removed
                    'related_tours' => $relatedTours->map(function ($t) {
                        $relatedCategories = collect([]);
                        if (!empty($t->category_ids)) {
                            $catIds = is_string($t->category_ids) ? json_decode($t->category_ids, true) : $t->category_ids;
                            if (is_array($catIds) && count($catIds) > 0) {
                                $relatedCategories = \Modules\Tour\Models\TourCategory::whereIn('id', $catIds)->select('id', 'name', 'slug')->get();
                            }
                        }
                        
                        return [
                            'id' => $t->id,
                            'title' => $t->title,
                            'slug' => $t->slug,
                            'price' => $t->price,
                            'sale_price' => $t->sale_price,
                            'duration' => $t->duration,
                            'duration_type' => $t->duration_type,
                            'nights' => $t->nights,
                            'tour_type' => $t->tour_type,
                            'image_url' => $t->image_id ? get_file_url($t->image_id, 'full') : null,
                            'destination' => $t->location ? [
                                'id' => $t->location->id,
                                'name' => $t->location->name,
                                'slug' => $t->location->slug,
                            ] : null,
                            'categories' => $relatedCategories,
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
    // Get approved/featured reviews for homepage/success stories
    Route::get('/', function (Request $request) {
        try {
            $query = \Modules\Review\Models\Review::query()
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

            // Eager load author
            $query->with(['author']);

            $reviews = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));

            // Transform to match frontend expectations
            $data = $reviews->getCollection()->map(function ($review) {
                // Get Meta
                $meta = \Modules\Review\Models\ReviewMeta::where('review_id', $review->id)->pluck('val', 'name')->toArray();

                // Fetch Images
                $images = [];
                $imageIds = [];
                $reviewImagesMeta = \Modules\Review\Models\ReviewMeta::where('review_id', $review->id)->where('name', 'review_image')->get();
                foreach ($reviewImagesMeta as $imgMeta) {
                    $imageIds[] = $imgMeta->val;
                }

                if (!empty($imageIds)) {
                    $mediaFiles = \Modules\Media\Models\MediaFile::whereIn('id', $imageIds)->get();
                    foreach ($mediaFiles as $media) {
                        $images[] = get_file_url($media->id, 'full');
                    }
                }

                // Fallback image if no review images
                $mainImage = !empty($images) ? $images[0] : null;

                // Get Associated Object (Tour) details
                $tourName = null;
                $tourLink = null;
                $tourImage = null;

                if ($review->object_model === 'tour' && $review->object_id) {
                    $tour = \Modules\Tour\Models\Tour::find($review->object_id);
                    if ($tour) {
                        $tourName = $tour->title;
                        $tourLink = $tour->slug;
                        if (!$mainImage && $tour->image_id) {
                            $tourImage = get_file_url($tour->image_id, 'full');
                        }
                    }
                }

                return [
                    'id' => $review->id,
                    'object_id' => $review->object_id,
                    'object_model' => $review->object_model ?? 'tour',
                    'tour_id' => $review->object_id,
                    'tour_name' => $tourName,
                    'tour_slug' => $tourLink,
                    'rating' => $review->rate_number ?? $review->rating ?? 5,
                    'title' => $review->title ?? '',
                    'content' => $review->content ?? '',
                    'full_review' => $review->content ?? '',
                    'status' => $review->status,
                    'created_at' => $review->created_at,

                    // Prioritize Columns over Meta
                    'author_id' => $review->author_id,
                    'author_name' => $review->author_name ?? $meta['author_name'] ?? ($review->author ? $review->author->name : 'Anonymous'),
                    'author_email' => $review->author_email ?? $meta['author_email'] ?? ($review->author ? $review->author->email : null),
                    'author_avatar' => $review->author_avatar ?? $meta['author_avatar'] ?? ($review->author ? get_file_url($review->author->avatar_id, 'full') : null),
                    'author_location' => $review->author_location ?? $meta['author_location'] ?? null,
                    'author_country' => $review->author_country ?? $meta['author_country'] ?? null,
                    'review_source' => $review->review_source ?? $meta['review_source'] ?? 'website',
                    'date' => $review->review_date ?? $meta['review_date'] ?? $review->created_at->format('Y-m-d'),
                    'trip_summary' => $review->trip_summary ?? null, // Added for completeness

                    'images' => $images,
                    'image' => $mainImage ?? $tourImage
                ];
            });

            return response()->json([
                'data' => $data,
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });


    // Get reviews for a specific tour/object (public)
    Route::get('/{objectModel}/{objectId}', function ($objectModel, $objectId) {
        try {
            $reviews = DB::table('bc_review')
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
                'status' => 'pending',
                'author_id' => auth('sanctum')->id() ?? null,

                // Extended Fields (Direct Column Save)
                'author_name' => $request->input('author_name'),
                'author_email' => $request->input('author_email'),
                'author_avatar' => $request->input('author_avatar'),
                'author_location' => $request->input('author_location'),
                'author_country' => $request->input('author_country'),
                'review_source' => $request->input('review_source', 'website'),
                'review_date' => $request->input('review_date', date('Y-m-d')),
            ]);

            // Link to existing user if email matches
            if (!$review->author_id && $review->author_email) {
                $user = \App\User::where('email', $review->author_email)->first();
                if ($user) {
                    $review->author_id = $user->id;
                }
            }

            $review->save();
            
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

            $lang = $request->query('lang');
            $posts = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 10);

            // Transform data with images and author
            $data = $posts->map(function ($post) use ($lang) {
                $translation = $lang ? $post->translate($lang) : null;
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
                    'title' => $translation->title ?? $post->title,
                    'slug' => $post->slug,
                    'content' => $translation->content ?? $post->content,
                    'excerpt' => $translation->excerpt ?? $post->excerpt,
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
            $lang = $request->query('lang');
            $posts = \Modules\News\Models\News::where('status', 'publish')
                ->where('is_featured', 1)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            $data = $posts->map(function ($post) use ($lang) {
                $translation = $lang ? $post->translate($lang) : null;
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
                    'title' => $translation->title ?? $post->title,
                    'slug' => $post->slug,
                    'content' => $translation->content ?? $post->content,
                    'excerpt' => $translation->excerpt ?? $post->excerpt,
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
    Route::get('/{slug}', function (Request $request, $slug) {
        try {
            $post = \Modules\News\Models\News::where('slug', $slug)
                ->where('status', 'publish')
                ->with(['location']) // Eager load location
                ->first();
            
            if (!$post) {
                return response()->json(['error' => 'Post not found'], 404);
            }

            // Get translation if lang param is provided
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $post->translate($lang);
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
            $relatedPostsResolved = [];
            
            // 1. Try to get manually assigned related posts IDs
            $manualRelatedIds = $post->related_posts;
            if (is_string($manualRelatedIds)) {
                $manualRelatedIds = json_decode($manualRelatedIds, true);
            }
            
            if (!empty($manualRelatedIds) && is_array($manualRelatedIds)) {
                $relatedPostsResolved = \Modules\News\Models\News::where('status', 'publish')
                    ->whereIn('id', $manualRelatedIds)
                    ->get();
            }
            
            // 2. Fallback to Category-based related posts if manual list is empty
            if (empty($relatedPostsResolved) || $relatedPostsResolved->isEmpty()) {
                $relatedPostsResolved = \Modules\News\Models\News::where('status', 'publish')
                    ->where('id', '!=', $post->id)
                    ->when($post->cat_id, function ($q) use ($post) {
                        return $q->where('cat_id', $post->cat_id);
                    })
                    ->orderBy('id', 'desc')
                    ->limit(4)
                    ->get();
            }

            // 3. Map to output format (Translate related posts titles too?) 
            // For now, let's keep related posts simple or translate them if possible. 
            // Ideally related posts should also respect lang, but it might be expensive.
            // Let's stick to main post translation first.

            $related = $relatedPostsResolved->map(function ($p) use ($getImageUrl) {
                return [
                    'id' => $p->id,
                    'title' => $p->title, // Could translate this too if needed
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
                    'title' => $translation->title ?? $post->title,
                    'slug' => $post->slug,
                    'content' => $translation->content ?? $post->content,
                    'excerpt' => $translation->excerpt ?? $post->excerpt,
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
                    'author_bio' => $post->author_bio,
                    'reading_time' => $post->reading_time ?? null,
                    'publish_date' => $post->created_at ? $post->created_at->format('Y-m-d') : null,
                    'created_at' => $post->created_at,
                    // Duplicate key removal: 'created_at' => $post->created_at,
                    'og_title' => $post->og_title,
                    'og_description' => $post->og_description,
                    'og_image_url' => $post->og_image_id ? parse_url(get_file_url($post->og_image_id, 'full'), PHP_URL_PATH) : null,
                    'twitter_title' => $post->twitter_title,
                    'twitter_description' => $post->twitter_description,
                    'twitter_image_url' => $post->twitter_image_id ? parse_url(get_file_url($post->twitter_image_id, 'full'), PHP_URL_PATH) : null,
                    'twitter_card' => $post->twitter_card,
                    'canonical_url' => $post->canonical_url,
                    'robots_meta' => $post->robots_meta,
                    'schema_markup' => $post->schema_markup,
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

            // Transform with translation if lang is provided
            $lang = $request->query('lang');
            $needTranslation = $lang && !is_default_lang($lang);

            return response()->json([
                'data' => $pages->map(function ($page) use ($needTranslation, $lang) {
                    $title = $page->title;
                    if ($needTranslation) {
                        $translation = $page->translate($lang);
                        if ($translation && $translation->title) {
                            $title = $translation->title;
                        }
                    }

                    return [
                        'id' => $page->id,
                        'title' => $title,
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
    Route::get('/homepage', function (Request $request) {
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

            if (!$page) {
                return response()->json(['error' => 'Homepage not found'], 404);
            }
            
            $pageData = $page->toArray();

            // Handle Translations
            $lang = $request->query('lang');
            if ($lang && !is_default_lang($lang)) {
                $translation = $page->translate($lang);
                if ($translation) {
                    $pageData['title'] = $translation->title ?? $pageData['title'];
                    $pageData['content'] = $translation->content ?? $pageData['content'];
                    $pageData['short_desc'] = $translation->short_desc ?? $pageData['short_desc'];
                    $pageData['banner_title'] = $translation->banner_title ?? $pageData['banner_title'];
                }
            }

            $pageData['og_image_url'] = $page->og_image_id ? get_file_url($page->og_image_id, 'full') : null;
            $pageData['twitter_image_url'] = $page->twitter_image_id ? get_file_url($page->twitter_image_id, 'full') : null;
            $pageData['banner_image_url'] = $page->banner_image_id ? get_file_url($page->banner_image_id, 'full') : null;
            
            return response()->json(['data' => $pageData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Get page by slug (must be last due to wildcard)
    Route::get('/{slug}', function (Request $request, $slug) {
        try {
            $page = \Modules\Page\Models\Page::where('slug', $slug)
                ->where('status', 'publish')
                ->first();
            
            if (!$page) {
                return response()->json(['error' => 'Page not found'], 404);
            }
            
            $pageData = $page->toArray();

            // Handle Translations
            $lang = $request->query('lang');
            if ($lang && !is_default_lang($lang)) {
                $translation = $page->translate($lang);
                if ($translation) {
                    $pageData['title'] = $translation->title ?? $pageData['title'];
                    $pageData['content'] = $translation->content ?? $pageData['content'];
                    $pageData['short_desc'] = $translation->short_desc ?? $pageData['short_desc'];
                    $pageData['banner_title'] = $translation->banner_title ?? $pageData['banner_title'];
                }
            }

            $pageData['og_image_url'] = $page->og_image_id ? get_file_url($page->og_image_id, 'full') : null;
            $pageData['twitter_image_url'] = $page->twitter_image_id ? get_file_url($page->twitter_image_id, 'full') : null;
            
            return response()->json(['data' => $pageData]);
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
        $table = Schema::hasTable('bc_contact_submissions') ? 'bc_contact_submissions' : 'bc_contact';

        $id = DB::table($table)->insertGetId([
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


// =====================================================
// SEO API (Public Access)
// =====================================================
Route::prefix('module/core/seo')->group(function () {
    // Global SEO Settings
    Route::get('/global', function () {
        try {
            $settings = Settings::getSettings('seo');
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });

    // Sitemap Settings
    Route::get('/sitemap', function () {
        try {
            $settings = Settings::getSettings('sitemap');
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([]);
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
});
