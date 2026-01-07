<?php

/**
 * ADMIN PAGE MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Page\Models\Page;

// =====================================================
// PAGE MANAGEMENT
// =====================================================
Route::prefix('module/page')->middleware('auth:sanctum')->group(function () {
    // Get all pages
    Route::get('/', function (Request $request) {
        try {
            $query = Page::with('author');
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where('title', 'LIKE', '%' . $request->s . '%');
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Template filter
            if ($request->has('template') && $request->template) {
                $query->where('template', $request->template);
            }
            
            $pages = $query->orderBy('id', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $pages->items(),
                'total' => $pages->total(),
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single page for editing
    Route::get('/edit/{id}', function ($id) {
        try {
            $page = Page::with('author')->findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content' => $page->content,
                    'short_desc' => $page->short_desc,
                    'image_id' => $page->image_id,
                    'banner_image_id' => $page->banner_image_id,
                    'template' => $page->template,
                    'status' => $page->status,
                    'seo_title' => $page->seo_title,
                    'seo_description' => $page->seo_description,
                    'author' => $page->author,
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store/Update page
    Route::post('/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $page = Page::findOrFail($id);
            } else {
                $page = new Page();
                $page->create_user = auth()->id();
            }
            
            $page->title = $request->input('title');
            $page->slug = $request->input('slug') ?: \Str::slug($request->input('title'));
            $page->content = $request->input('content');
            $page->short_desc = $request->input('short_desc');
            $page->image_id = $request->input('image_id');
            $page->banner_image_id = $request->input('banner_image_id');
            $page->template = $request->input('template', 'default');
            $page->status = $request->input('status', 'publish');
            $page->seo_title = $request->input('seo_title');
            $page->seo_description = $request->input('seo_description');
            $page->update_user = auth()->id();
            
            $page->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $page->id],
                'message' => 'Page saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete page
    Route::delete('/{id}', function ($id) {
        try {
            $page = Page::findOrFail($id);
            $page->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully',
            ]);
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
                    Page::whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    Page::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    Page::whereIn('id', $ids)->update(['status' => 'draft']);
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
    
    // Get templates
    Route::get('/templates', function () {
        try {
            return response()->json([
                ['value' => 'default', 'label' => 'Default'],
                ['value' => 'home', 'label' => 'Home Page'],
                ['value' => 'contact', 'label' => 'Contact Page'],
                ['value' => 'about', 'label' => 'About Page'],
                ['value' => 'full-width', 'label' => 'Full Width'],
                ['value' => 'sidebar', 'label' => 'With Sidebar'],
            ]);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Get statistics
    Route::get('/statistics', function () {
        try {
            $stats = [
                'total' => Page::count(),
                'published' => Page::where('status', 'publish')->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
});
