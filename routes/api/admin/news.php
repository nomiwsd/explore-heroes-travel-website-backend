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
            $post = News::with(['category', 'author', 'tags'])->findOrFail($id);
            $seoMeta = $post->getSeoMeta();
            
            // Get tag IDs from pivot table relationship
            $tagIds = $post->tags->pluck('id')->toArray();
            
            $relatedPosts = [];
            if ($post->related_posts && is_string($post->related_posts)) {
                $relatedPosts = json_decode($post->related_posts, true) ?: [];
            }
            
            $gallery = [];
            if ($post->gallery && is_string($post->gallery)) {
                $gallery = json_decode($post->gallery, true) ?: [];
            }
            
            return response()->json([
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'short_desc' => $post->short_desc,
                    'excerpt' => $post->excerpt,
                    'image_id' => $post->image_id,
                    'og_image_id' => $post->og_image_id,
                    'image_alt' => $post->image_alt,
                    'image_url' => $post->image_url ?? null,
                    'cat_id' => $post->cat_id,
                    'category_id' => $post->cat_id, // Alias for frontend
                    'location_id' => $post->location_id,
                    'status' => $post->status,
                    'is_featured' => $post->is_featured,
                    'author_bio' => $post->author_bio,
                    'reading_time' => $post->reading_time,
                    'tag_ids' => $tagIds,
                    'related_posts' => $relatedPosts,
                    'gallery' => $gallery,
                    'meta_title' => $seoMeta['seo_title'] ?? null,
                    'meta_desc' => $seoMeta['seo_desc'] ?? null,
                    'meta_keywords' => null, // Not stored in bc_seo table
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
                
                // Basic fields
                $post->title = $request->input('title');
                $post->slug = $request->input('slug') ?: \Str::slug($request->input('title'));
                $post->content = $request->input('content');
                $post->short_desc = $request->input('short_desc');
                $post->excerpt = $request->input('excerpt');
                
                // Image fields
                $post->image_id = $request->input('image_id');
                $post->og_image_id = $request->input('og_image_id');
                $post->image_alt = $request->input('image_alt');
                
                // Category and location (map category_id to cat_id)
                $post->cat_id = $request->input('cat_id') ?: $request->input('category_id');
                $post->location_id = $request->input('location_id');
                
                // Status and featured
                $post->status = $request->input('status', 'publish');
                $post->is_featured = $request->input('is_featured', 0);
                
                // Additional fields
                $post->author_bio = $request->input('author_bio');
                $post->reading_time = $request->input('reading_time');
                
                // Array fields (stored as JSON)
                if ($request->has('related_posts')) {
                    $post->related_posts = json_encode($request->input('related_posts'));
                }
                if ($request->has('gallery')) {
                    $post->gallery = json_encode($request->input('gallery'));
                }
                
                $post->update_user = auth()->id();
                $post->save();
                
                // Handle tags through pivot table (core_news_tag)
                if ($request->has('tag_ids')) {
                    $post->saveTag([], $request->input('tag_ids'));
                }
                
                // Save SEO meta data to separate bc_seo table
                if ($request->has('meta_title') || $request->has('meta_desc') || $request->has('meta_keywords')) {
                    $seoRequest = new Request([
                        'seo_title' => $request->input('meta_title'),
                        'seo_desc' => $request->input('meta_desc'),
                        'seo_image' => $request->input('og_image_id') ?: $request->input('image_id'),
                    ]);
                    $post->saveSEO($seoRequest);
                }
                
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
            $seoMeta = $category->getSeoMeta();
            
            return response()->json([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'content' => $category->content,
                    'description' => $category->content, // Alias for frontend
                    'image_id' => $category->image_id,
                    'status' => $category->status,
                    'meta_title' => $seoMeta['seo_title'] ?? null,
                    'meta_desc' => $seoMeta['seo_desc'] ?? null,
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
            // Frontend sends 'description', backend stores as 'content'
            $category->content = $request->input('content') ?: $request->input('description');
            $category->image_id = $request->input('image_id');
            $category->status = $request->input('status', 'publish');
            $category->save();
            
            // Save SEO meta data
            if ($request->has('meta_title') || $request->has('meta_desc')) {
                $seoRequest = new Request([
                    'seo_title' => $request->input('meta_title'),
                    'seo_desc' => $request->input('meta_desc'),
                    'seo_image' => $request->input('image_id'),
                ]);
                $category->saveSEO($seoRequest);
            }
            
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
