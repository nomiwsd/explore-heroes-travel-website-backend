<?php

/**
 * ADMIN NEWS/BLOG MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;

// =====================================================
// NEWS/BLOG MANAGEMENT
// =====================================================
Route::prefix('module/news')->group(function () {
    // Get all news posts (no auth required for listing in admin dashboard)
    Route::get('/', function (Request $request) {
        try {
            $query = News::with(['category', 'author']);
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where('title', 'LIKE', '%' . $request->s . '%');
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Category filter
            if ($request->has('category_id') && $request->category_id) {
                $query->where('cat_id', $request->category_id);
            }
            
            $posts = $query->orderBy('id', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $posts->items(),
                'total' => $posts->total(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single post for editing
    Route::get('/edit/{id}', function ($id) {
        try {
            $post = News::with(['category', 'author'])->findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'short_desc' => $post->short_desc,
                    'image_id' => $post->image_id,
                    'image_url' => $post->image_url ?? null,
                    'cat_id' => $post->cat_id,
                    'status' => $post->status,
                    'seo_title' => $post->seo_title,
                    'seo_description' => $post->seo_description,
                    'category' => $post->category,
                    'author' => $post->author,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Store/Update post
        Route::post('/store/{id?}', function (Request $request, $id = null) {
            try {
                if ($id) {
                    $post = News::findOrFail($id);
                } else {
                    $post = new News();
                    $post->create_user = auth()->id();
                }
                
                $post->title = $request->input('title');
                $post->slug = $request->input('slug') ?: \Str::slug($request->input('title'));
                $post->content = $request->input('content');
                $post->short_desc = $request->input('short_desc');
                $post->image_id = $request->input('image_id');
                $post->cat_id = $request->input('cat_id');
                $post->status = $request->input('status', 'publish');
                $post->seo_title = $request->input('seo_title');
                $post->seo_description = $request->input('seo_description');
                $post->update_user = auth()->id();
                
                $post->save();
                
                return response()->json([
                    'success' => true,
                    'data' => ['id' => $post->id],
                    'message' => 'Post saved successfully',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Delete post
        Route::delete('/{id}', function ($id) {
            try {
                $post = News::findOrFail($id);
                $post->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Post deleted successfully',
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
                        News::whereIn('id', $ids)->delete();
                        break;
                    case 'publish':
                        News::whereIn('id', $ids)->update(['status' => 'publish']);
                        break;
                    case 'draft':
                        News::whereIn('id', $ids)->update(['status' => 'draft']);
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
        
        // Get statistics
        Route::get('/statistics', function () {
            try {
                $stats = [
                    'total' => News::count(),
                    'published' => News::where('status', 'publish')->count(),
                    'draft' => News::where('status', 'draft')->count(),
                    'by_category' => NewsCategory::withCount('posts')
                        ->get()
                        ->map(fn($cat) => ['id' => $cat->id, 'name' => $cat->name, 'count' => $cat->posts_count]),
                ];
                
                return response()->json($stats);
            } catch (\Exception $e) {
                return response()->json([]);
            }
        });
    }); // Close auth:sanctum middleware group
});

// =====================================================
// NEWS CATEGORY MANAGEMENT
// =====================================================
Route::prefix('module/news/category')->middleware('auth:sanctum')->group(function () {
    // Get all categories
    Route::get('/', function (Request $request) {
        try {
            $query = NewsCategory::query();
            
            if ($request->has('s') && $request->s) {
                $query->where('name', 'LIKE', '%' . $request->s . '%');
            }
            
            $categories = $query->withCount('posts')->orderBy('id', 'desc')->get();
            
            return response()->json([
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'image_id' => $category->image_id,
                        'status' => $category->status,
                        'posts_count' => $category->posts_count,
                    ];
                }),
                'total' => $categories->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single category
    Route::get('/edit/{id}', function ($id) {
        try {
            $category = NewsCategory::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'content' => $category->content,
                    'image_id' => $category->image_id,
                    'status' => $category->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store/Update category
    Route::post('/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $category = NewsCategory::findOrFail($id);
            } else {
                $category = new NewsCategory();
            }
            
            $category->name = $request->input('name');
            $category->slug = $request->input('slug') ?: \Str::slug($request->input('name'));
            $category->content = $request->input('content');
            $category->image_id = $request->input('image_id');
            $category->status = $request->input('status', 'publish');
            $category->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $category->id],
                'message' => 'Category saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete category
    Route::delete('/{id}', function ($id) {
        try {
            $category = NewsCategory::findOrFail($id);
            $category->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit categories
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    NewsCategory::whereIn('id', $ids)->delete();
                    break;
                case 'publish':
                    NewsCategory::whereIn('id', $ids)->update(['status' => 'publish']);
                    break;
                case 'draft':
                    NewsCategory::whereIn('id', $ids)->update(['status' => 'draft']);
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
// NEWS TAG MANAGEMENT
// =====================================================
Route::prefix('module/news/tag')->middleware('auth:sanctum')->group(function () {
    // Get all tags
    Route::get('/', function (Request $request) {
        try {
            $query = \Modules\News\Models\Tag::query();
            
            if ($request->has('s') && $request->s) {
                $query->where('name', 'LIKE', '%' . $request->s . '%');
            }
            
            $tags = $query->orderBy('id', 'desc')->paginate($request->input('limit', 50));
            
            return response()->json([
                'data' => $tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'content' => $tag->content,
                    ];
                }),
                'total' => $tags->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single tag
    Route::get('/edit/{id}', function ($id) {
        try {
            $tag = \Modules\News\Models\Tag::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'content' => $tag->content,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store/Update tag
    Route::post('/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $tag = \Modules\News\Models\Tag::findOrFail($id);
            } else {
                $tag = new \Modules\News\Models\Tag();
            }
            
            $tag->name = $request->input('name');
            $tag->slug = $request->input('slug') ?: \Str::slug($request->input('name'));
            $tag->content = $request->input('content');
            $tag->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $tag->id],
                'message' => 'Tag saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete tag
    Route::delete('/{id}', function ($id) {
        try {
            $tag = \Modules\News\Models\Tag::findOrFail($id);
            $tag->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tag deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit tags
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    \Modules\News\Models\Tag::whereIn('id', $ids)->delete();
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
