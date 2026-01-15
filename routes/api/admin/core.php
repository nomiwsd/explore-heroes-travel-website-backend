<?php

/**
 * ADMIN CORE MODULE ROUTES
 * Menu, Settings, SEO management
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
            $id = $id ? $id : $request->input('id');
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
                    'items' => \DB::table('core_pages')
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
                    'items' => \DB::table('core_news')
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
                    $items = \DB::table('core_pages')
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
                    $query = \DB::table('core_pages')->where('status', 'publish');
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
            $sitemapContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $sitemapContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            $baseUrl = config('app.url', 'https://exploreheros.com');
            
            $sitemapContent .= "<url><loc>{$baseUrl}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
            
            // Tours
            $tours = Tour::where('status', 'publish')->get(['slug']);
            foreach ($tours as $tour) {
                $sitemapContent .= "<url><loc>{$baseUrl}/tours/{$tour->slug}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
            }
            
            // Destinations
            $destinations = Location::where('status', 'publish')->get(['slug']);
            foreach ($destinations as $dest) {
                $sitemapContent .= "<url><loc>{$baseUrl}/destinations/{$dest->slug}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
            }
            
            // Pages
            $pages = \DB::table('core_pages')->where('status', 'publish')->get(['slug']);
            foreach ($pages as $page) {
                $sitemapContent .= "<url><loc>{$baseUrl}/{$page->slug}</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>\n";
            }
            
            // Blog
            $posts = \DB::table('core_news')->where('status', 'publish')->get(['slug']);
            foreach ($posts as $post) {
                $sitemapContent .= "<url><loc>{$baseUrl}/blog/{$post->slug}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
            }
            
            $sitemapContent .= '</urlset>';
            
            $sitemapPath = public_path('sitemap.xml');
            file_put_contents($sitemapPath, $sitemapContent);
            
            return response()->json(['success' => true, 'url' => $baseUrl . '/sitemap.xml']);
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
    // ... (existing routes)
});

// =====================================================
// AUDIT LOGS MANAGEMENT
// =====================================================
Route::prefix('module/core/audit-logs')->middleware(['auth:sanctum', 'permission:audit_log_view'])->group(function () {
    Route::get('/', [\Modules\Core\Controllers\AuditLogController::class, 'index']);
});
