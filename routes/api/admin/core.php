<?php

/**
 * ADMIN CORE MODULE ROUTES
 * Menu, Settings, SEO management
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\Settings;
use Modules\Core\Models\Menu;
use Modules\Location\Models\Location;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourCategory;

// =====================================================
// MENU MANAGEMENT
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

    // Get single menu (?lang= for reading translation)
    Route::get('/edit/{id}', function (Request $request, $id) {
        try {
            $menu = Menu::findOrFail($id);

            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $menu->forceTranslate($lang);
            }

            // Items: use translation items if available, else origin items
            $items = $translation && $translation->items
                ? (is_string($translation->items) ? json_decode($translation->items, true) : $translation->items)
                : $menu->items_json;

            return response()->json([
                'data' => [
                    'id' => $menu->id,
                    'name' => $menu->name, // name is not translated (it's an internal identifier)
                    'items' => $items,
                    'locations' => $menu->locations,
                    'status' => $menu->status,
                ],
                'translation' => $translation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Store/Update menu (?lang= or body.lang for translation save)
    Route::middleware('auth:sanctum')->post('/store/{id?}', function (Request $request, $id = null) {
        try {
            $id = $id ? $id : $request->input('id');
            $lang = $request->query('lang') ?: $request->input('lang');
            $isTranslation = $lang && !is_default_lang($lang);

            if ($id) {
                $menu = Menu::findOrFail($id);
            } else {
                if ($isTranslation) {
                    return response()->json(['error' => 'Cannot create translation for non-existing menu'], 400);
                }
                $menu = new Menu();
            }

            if (!$isTranslation) {
                // Default language: save origin
                $menu->name = $request->input('name');
                $menu->items = $request->input('items');
                $menu->locations = $request->input('locations', []);
                $menu->status = $request->input('status', 'publish');
                $menu->save();
                // Mirror to default-locale translation row (so future reads work uniformly)
                $menu->forceSaveTranslation($lang ?: get_main_lang(), [
                    'items' => $request->input('items'),
                ]);
                $message = 'Menu saved successfully';
            } else {
                // Non-default language: save ONLY translation, do NOT touch origin
                $menu->forceSaveTranslation($lang, [
                    'items' => $request->input('items'),
                ]);
                $message = 'Menu translation saved successfully';
            }

            return response()->json([
                'success' => true,
                'data' => ['id' => $menu->id],
                'message' => $message,
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
                    $items = Menu::whereIn('id', $ids)->get();
                    foreach ($items as $item) {
                       $item->delete(); // This triggers the 'deleted' event to clear cache
                    }
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
            $types = [
                [
                    'key' => 'custom',
                    'name' => 'Custom Link',
                    'items' => [],
                ],
                [
                    'key' => 'page',
                    'name' => 'Pages',
                    'items' => DB::table('core_pages')
                        ->where('status', 'publish')
                        ->select('id', 'title as name', 'slug')
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/' . $item->slug]),
                ],
                [
                    'key' => 'tour',
                    'name' => 'Tours',
                    'items' => Tour::where('status', 'publish')
                        ->select('id', 'title as name', 'slug')
                        ->limit(50)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/tours/' . $item->slug]),
                ],
                [
                    'key' => 'location',
                    'name' => 'Destinations',
                    'items' => Location::where('status', 'publish')
                        ->select('id', 'name', 'slug')
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/destinations/' . $item->slug]),
                ],
                [
                    'key' => 'news',
                    'name' => 'News/Blog',
                    'items' => DB::table('core_news')
                        ->where('status', 'publish')
                        ->select('id', 'title as name', 'slug')
                        ->limit(50)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/blogs/' . $item->slug]),
                ],
                [
                    'key' => 'tour_category',
                    'name' => 'Tour Categories',
                    'items' => TourCategory::where('status', 'publish')
                        ->select('id', 'name', 'slug')
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/tours?category=' . $item->slug]),
                ],
            ];

            return response()->json($types);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
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
                    $items = DB::table('core_pages')
                        ->where('status', 'publish')
                        ->where('title', 'LIKE', '%' . $query . '%')
                        ->select('id', 'title as name', 'slug')
                        ->limit(20)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/' . $item->slug]);
                    break;
                case 'tour':
                    $items = Tour::where('status', 'publish')
                        ->where('title', 'LIKE', '%' . $query . '%')
                        ->select('id', 'title as name', 'slug')
                        ->limit(20)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/tours/' . $item->slug]);
                    break;
                case 'location':
                    $items = Location::where('status', 'publish')
                        ->where('name', 'LIKE', '%' . $query . '%')
                        ->select('id', 'name', 'slug')
                        ->limit(20)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/destinations/' . $item->slug]);
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
                    $query = DB::table('core_pages')->where('status', 'publish');
                    $total = $query->count();
                    $items = $query->select('id', 'title as name', 'slug')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/' . $item->slug]);
                    break;
                case 'tour':
                    $query = Tour::where('status', 'publish');
                    $total = $query->count();
                    $items = $query->select('id', 'title as name', 'slug')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/tours/' . $item->slug]);
                    break;
                case 'location':
                    $query = Location::where('status', 'publish');
                    $total = $query->count();
                    $items = $query->select('id', 'name', 'slug')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get()
                        ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'url' => '/destinations/' . $item->slug]);
                    break;
            }

            return response()->json(['data' => $items, 'total' => $total]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'total' => 0]);
        }
    });
});

// =====================================================
// SETTINGS MANAGEMENT
// =====================================================
Route::prefix('module/core/settings')->group(function () {
    // Get settings by group — locale-aware
    Route::get('/index/{group?}', function (Request $request, $group = 'general') {
        try {
            $lang = $request->query('lang');
            $settings = Settings::getSettings($group, $lang);
            return response()->json([
                'settings' => $settings,
                'group'    => $group,
                'lang'     => $lang,
                'translatable_keys' => Settings::TRANSLATABLE_KEYS,
            ]);
        } catch (\Exception $e) {
            return response()->json(['settings' => [], 'group' => $group, 'error' => $e->getMessage()]);
        }
    });

    // Update settings — locale-aware. When ?lang=xx is non-default, only
    // translatable keys are persisted to per-locale rows; other keys are ignored
    // (they live on the default row only and shouldn't change per locale).
    Route::middleware('auth:sanctum')->post('/store/{group?}', function (Request $request, $group = 'general') {
        try {
            $data = $request->all();
            $lang = $request->input('lang') ?: $request->query('lang');
            $isMultiLang = !empty($lang) && (function_exists('is_default_lang') ? !is_default_lang($lang) : $lang !== 'en');

            foreach ($data as $key => $value) {
                if ($key === '_token' || $key === 'lang') continue;

                // For non-default lang, skip non-translatable keys to preserve global config
                if ($isMultiLang && !Settings::isTranslatable($key)) {
                    continue;
                }
                Settings::store($key, $value, $group, $isMultiLang ? $lang : null);
            }

            return response()->json([
                'success' => true,
                'message' => $isMultiLang ? "Settings translation ({$lang}) saved" : 'Settings updated successfully',
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
// SEO MANAGEMENT
// =====================================================
Route::prefix('module/core/seo')->middleware('auth:sanctum')->group(function () {
    // Global SEO Settings
    // Route::get('/global', function () {
    //     try {
    //         $settings = Settings::getSettings('seo');
    //         return response()->json($settings);
    //     } catch (\Exception $e) {
    //         return response()->json([]);
    //     }
    // });

    Route::post('/global', function (Request $request) {
        try {
            $lang = $request->query('lang') ?: $request->input('lang');
            $data = $request->all();
            foreach ($data as $key => $value) {
                if ($key === '_token' || $key === 'lang') {
                    continue;
                }
                // Settings::store() saves a per-locale row only for translatable
                // keys (meta_description, og_title, etc.); global keys (analytics
                // IDs, toggles, schema) ignore $lang and stay shared.
                Settings::store($key, $value, 'seo', $lang);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // 301 Redirects
    Route::get('/redirects', function (Request $request) {
        try {
            if (!Schema::hasTable('bc_redirects')) {
                return response()->json(['data' => []]);
            }

            $query = DB::table('bc_redirects');

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
            $redirect = DB::table('bc_redirects')->where('id', $id)->first();
            return response()->json($redirect);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    Route::post('/redirects/store/{id?}', function (Request $request, $id = null) {
        try {
            if (!Schema::hasTable('bc_redirects')) {
                Schema::create('bc_redirects', function ($table) {
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
                DB::table('bc_redirects')->where('id', $id)->update($data);
            } else {
                $data['created_at'] = now();
                $id = DB::table('bc_redirects')->insertGetId($data);
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
                DB::table('bc_redirects')->whereIn('id', $ids)->delete();
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
            // Settings are stored with 'sitemap_' prefix — strip it and cast types
            return response()->json([
                'enabled'              => filter_var($settings['sitemap_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'include_pages'        => filter_var($settings['sitemap_include_pages'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'include_tours'        => filter_var($settings['sitemap_include_tours'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'include_destinations' => filter_var($settings['sitemap_include_destinations'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'include_blog'         => filter_var($settings['sitemap_include_blog'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'frequency'            => $settings['sitemap_frequency'] ?? 'weekly',
                'priority'             => (float)($settings['sitemap_priority'] ?? 0.8),
                'exclude_urls'         => json_decode($settings['sitemap_exclude_urls'] ?? '[]', true) ?: [],
                'custom_urls'          => json_decode($settings['sitemap_custom_urls'] ?? '[]', true) ?: [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'enabled'              => true,
                'include_pages'        => true,
                'include_tours'        => true,
                'include_destinations' => true,
                'include_blog'         => true,
                'frequency'            => 'weekly',
                'priority'             => 0.8,
                'exclude_urls'         => [],
                'custom_urls'          => [],
            ]);
        }
    });

    Route::post('/sitemap', function (Request $request) {
        try {
            $data = $request->all();
            foreach ($data as $key => $value) {
                if ($key !== '_token') {
                    // JSON-encode arrays before storing
                    $value = is_array($value) ? json_encode($value) : $value;
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
            // Load saved sitemap settings
            $raw              = Settings::getSettings('sitemap');
            $includePages     = filter_var($raw['sitemap_include_pages']        ?? true,  FILTER_VALIDATE_BOOLEAN);
            $includeTours     = filter_var($raw['sitemap_include_tours']        ?? true,  FILTER_VALIDATE_BOOLEAN);
            $includeDest      = filter_var($raw['sitemap_include_destinations'] ?? true,  FILTER_VALIDATE_BOOLEAN);
            $includeBlog      = filter_var($raw['sitemap_include_blog']         ?? true,  FILTER_VALIDATE_BOOLEAN);
            $frequency        = $raw['sitemap_frequency'] ?? 'weekly';
            $priority         = (float)($raw['sitemap_priority'] ?? 0.8);
            $excludeUrls      = json_decode($raw['sitemap_exclude_urls'] ?? '[]', true) ?: [];

            $sitemapContent  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $sitemapContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $baseUrl = rtrim(config('app.url', 'https://exploreheros.com'), '/');

            $sitemapContent .= "<url><loc>{$baseUrl}/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\n";

            // Tours
            if ($includeTours) {
                $tours = Tour::where('status', 'publish')->get(['slug']);
                foreach ($tours as $tour) {
                    if (!$tour->slug) continue;
                    $url = "{$baseUrl}/tours/{$tour->slug}";
                    if (in_array($url, $excludeUrls)) continue;
                    $sitemapContent .= "<url><loc>{$url}</loc><changefreq>{$frequency}</changefreq><priority>{$priority}</priority></url>\n";
                }
            }

            // Destinations
            if ($includeDest) {
                $destinations = Location::where('status', 'publish')->get(['slug']);
                foreach ($destinations as $dest) {
                    if (!$dest->slug) continue;
                    $url = "{$baseUrl}/destinations/{$dest->slug}";
                    if (in_array($url, $excludeUrls)) continue;
                    $sitemapContent .= "<url><loc>{$url}</loc><changefreq>{$frequency}</changefreq><priority>{$priority}</priority></url>\n";
                }
            }

            // Pages (raw query – manually exclude soft-deleted rows)
            if ($includePages) {
                $pages = DB::table('core_pages')
                    ->where('status', 'publish')
                    ->whereNull('deleted_at')
                    ->get(['slug']);
                foreach ($pages as $page) {
                    if (!$page->slug) continue;
                    $url = "{$baseUrl}/{$page->slug}";
                    if (in_array($url, $excludeUrls)) continue;
                    $sitemapContent .= "<url><loc>{$url}</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>\n";
                }
            }

            // Blog (raw query – manually exclude soft-deleted rows)
            if ($includeBlog) {
                $posts = DB::table('core_news')
                    ->where('status', 'publish')
                    ->whereNull('deleted_at')
                    ->get(['slug']);
                foreach ($posts as $post) {
                    if (!$post->slug) continue;
                    $url = "{$baseUrl}/blog/{$post->slug}";
                    if (in_array($url, $excludeUrls)) continue;
                    $sitemapContent .= "<url><loc>{$url}</loc><changefreq>{$frequency}</changefreq><priority>0.7</priority></url>\n";
                }
            }

            $sitemapContent .= '</urlset>';

            // Try public_path() first; fallback to base_path('public/')
            $sitemapPath = public_path('sitemap.xml');
            if (!is_writable(dirname($sitemapPath))) {
                $sitemapPath = base_path('public/sitemap.xml');
            }

            $written = file_put_contents($sitemapPath, $sitemapContent);
            if ($written === false) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Could not write sitemap to disk. Check write permissions for: ' . dirname($sitemapPath),
                ], 500);
            }

            return response()->json(['success' => true, 'url' => $baseUrl . '/sitemap.xml']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    });

    // Robots.txt
    Route::get('/robots', function () {
        try {
            $robotsPath = public_path('robots.txt');
            // If file exists, read from it; otherwise return default
            if (file_exists($robotsPath)) {
                $content = file_get_contents($robotsPath);
            } else {
                $content = "User-agent: *\nDisallow:\n\nSitemap: " . rtrim(config('app.url', 'https://exploreheros.com'), '/') . "/sitemap.xml";
            }
            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            return response()->json(['content' => '']);
        }
    });

    Route::post('/robots', function (Request $request) {
        try {
            $content = $request->input('content');
            $robotsPath = public_path('robots.txt');

            // Ensure public directory is writable
            if (!is_writable(dirname($robotsPath))) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot write to public directory. Check permissions.'
                ], 500);
            }

            $written = file_put_contents($robotsPath, $content);
            if ($written === false) {
                return response()->json([
                    'success' => false,
                    'error' => 'Could not write robots.txt to disk. Check write permissions.'
                ], 500);
            }

            return response()->json(['content' => $content, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    // ... (existing routes)
});

// =====================================================
// AUDIT LOGS MANAGEMENT
// =====================================================
Route::prefix('module/core/audit-logs')->middleware(['auth:sanctum', 'permission:audit_log_view'])->group(function () {
    Route::get('/', [\Modules\Core\Controllers\AuditLogController::class, 'index']);
});
