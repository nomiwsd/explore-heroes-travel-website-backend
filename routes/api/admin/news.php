<?php

/**
 * ADMIN NEWS/BLOG MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;
use Illuminate\Support\Str;

// =====================================================
// NEWS/BLOG MANAGEMENT
// =====================================================
Route::prefix('module/news')->middleware('auth:sanctum')->group(function () {
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
            
            $mappedPosts = collect($posts->items())->map(function($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'status' => $post->status,
                    'is_featured' => $post->is_featured,
                    'image_id' => $post->image_id,
                    'cat_id' => $post->cat_id,
                    'category_id' => $post->cat_id,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                    'category' => $post->category,
                    'author' => ($post->author && $post->author->id) ? [
                        'id' => $post->author->id,
                        'display_name' => $post->author->name ?: 'Unknown',
                        'email' => $post->author->email,
                    ] : ($post->create_user ? (function() use ($post) {
                        $u = \App\User::find($post->create_user);
                        return $u ? [
                            'id' => $u->id,
                            'display_name' => $u->name ?: 'Unknown',
                            'email' => $u->email,
                        ] : ['display_name' => 'Unknown'];
                    })() : ['display_name' => 'Unknown']),
                ];
            });
            
            return response()->json([
                'data' => $mappedPosts,
                'total' => $posts->total(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('permission:news_view');

    // Get single post for editing
    Route::get('/edit/{id}', function (Request $request, $id) {
        try {
            $post = News::with(['category', 'author', 'tags'])->findOrFail($id);

            // Get translation if lang param is provided
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $post->translate($lang);
            }

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
            
            // Get image file paths and construct URLs without double 'uploads' prefix
            $imageFilePath = null;
            $imageUrl = null;
            if ($post->image_id) {
                $media = \Modules\Media\Models\MediaFile::find($post->image_id);
                if ($media) {
                    $imageFilePath = $media->file_path;
                    // Construct URL properly - file_path already contains 'uploads/'
                    $imageUrl = url($media->file_path);
                }
            }
            
            $ogImageFilePath = null;
            $ogImageUrl = null;
            if ($post->og_image_id) {
                $media = \Modules\Media\Models\MediaFile::find($post->og_image_id);
                if ($media) {
                    $ogImageFilePath = $media->file_path;
                    // Construct URL properly - file_path already contains 'uploads/'
                    $ogImageUrl = url($media->file_path);
                }
            }

            $responseData = [
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                    'short_desc' => $post->short_desc,

                    // Mapped Image Fields (Featured)
                    'featured_image_id' => $post->image_id,
                    'featured_image_alt' => $post->image_alt,
                    'featured_image_url' => $post->image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($post->image_id)?->file_path : null,

                    // SEO Images (Relative Paths)
                    'og_image_url' => $post->og_image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($post->og_image_id)?->file_path : null,
                    'twitter_image_url' => $post->twitter_image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($post->twitter_image_id)?->file_path : null,
                    
                    // SEO Fields
                    'og_image_id' => $post->og_image_id,
                    'twitter_image_id' => $post->twitter_image_id, // Often needed for frontend logic
                    
                    'meta_title' => $seoMeta['seo_title'] ?? $post->meta_title ?? null,
                    'meta_desc' => $seoMeta['seo_desc'] ?? $post->meta_desc ?? null,
                    'meta_keywords' => $post->meta_keywords ?? null,
                    
                    'og_title' => $post->og_title,
                    'og_description' => $post->og_description,
                    
                    'twitter_title' => $post->twitter_title,
                    'twitter_description' => $post->twitter_description,
                    'twitter_card' => $post->twitter_card,
                    
                    'canonical_url' => $post->canonical_url,
                    'robots_meta' => $post->robots_meta,
                    'schema_markup' => $post->schema_markup,

                    // Categories & Tags
                    'category_id' => $post->cat_id,
                    'location_id' => $post->location_id,
                    'tag_ids' => $tagIds,
                    'related_posts' => $relatedPosts,
                    
                    // Meta
                    'status' => $post->status,
                    'is_featured' => $post->is_featured,
                    'author_bio' => $post->author_bio,
                    'reading_time' => $post->reading_time,
                    'publish_date' => $post->publish_date, // Added explicit field
                    'gallery' => $gallery,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,

                    // Relations
                    'category' => $post->category,
                    'author' => ($post->author && $post->author->id) ? [
                        'id' => $post->author->id,
                        'display_name' => $post->author->getDisplayName(),
                        'email' => $post->author->email,
                    ] : ($post->create_user ? [
                        'id' => $post->create_user,
                        'display_name' => \App\User::find($post->create_user)?->display_name ?? 'Unknown',
                        'email' => \App\User::find($post->create_user)?->email ?? '',
                    ] : [
                        'id' => auth()->id(),
                        'display_name' => auth()->user()?->display_name ?? 'Unknown',
                        'email' => auth()->user()?->email ?? '',
                    ]),
                ],
            ];

            // Include translation if fetched
            if ($translation) {
                $responseData['translation'] = $translation;
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('permission:news_view');
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Store/Update post
        Route::middleware('permission:news_update')->post('/store/{id?}', function (Request $request, $id = null) {
            try {
                $lang = $request->query('lang') ?: $request->input('lang');
                $isTranslation = $lang && !is_default_lang($lang);

                if ($id) {
                    $post = News::findOrFail($id);
                } else {
                    $post = new News();
                    $post->create_user = auth()->id();
                    $post->author_id = auth()->id(); // Set author to current user for new posts
                }

                // Only update main post fields if NOT a translation save
                if (!$isTranslation) {
                    // Basic fields
                    $post->title = $request->input('title');
                    $post->slug = $request->input('slug') ?: Str::slug($request->input('title'));
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

                    // PUBLISH DATE (Saved to explicit column)
                    if ($request->has('publish_date') && !empty($request->input('publish_date'))) {
                        $post->publish_date = \Carbon\Carbon::parse($request->input('publish_date'));
                    }

                    // Handle Featured Image ID (Frontend sends featured_image_id, backend uses image_id)
                    if ($request->has('featured_image_id')) {
                        $post->image_id = $request->input('featured_image_id');
                    }
                    if ($request->has('featured_image_alt')) {
                        $post->image_alt = $request->input('featured_image_alt');
                    }

                    // SEO & Social Fields (Saved directly to News model as per fillable)
                    $post->og_title = $request->input('og_title');
                    $post->og_description = $request->input('og_description');

                    $post->twitter_title = $request->input('twitter_title');
                    $post->twitter_description = $request->input('twitter_description');
                    $post->twitter_card = $request->input('twitter_card');
                    $post->twitter_image_id = $request->input('twitter_image_id');

                    $post->canonical_url = $request->input('canonical_url');
                    $post->robots_meta = $request->input('robots_meta');
                    $post->schema_markup = $request->input('schema_markup');

                    // Array fields (stored as JSON)
                    if ($request->has('related_posts')) {
                        $post->related_posts = json_encode($request->input('related_posts'));
                    }
                    if ($request->has('gallery')) {
                        $post->gallery = json_encode($request->input('gallery'));
                    }

                    // Meta keywords (saved directly to news table)
                    $post->meta_keywords = $request->input('meta_keywords');

                    // Set author_id if not set (for existing posts without author)
                    if (!$post->author_id) {
                        $post->author_id = auth()->id();
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

                    // Save default translation
                    $post->saveTranslation($lang ?: 'en', true);
                    $message = 'Post saved successfully';
                } else {
                    // Just save translation
                    $post->saveTranslation($lang, true);
                    $message = 'Translation saved successfully';
                }
                
                return response()->json([
                    'success' => true,
                    'data' => ['id' => $post->id],
                    'message' => $message,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });
        
        // Delete post
        Route::middleware('permission:news_delete')->delete('/{id}', function ($id) {
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
        Route::middleware('permission:news_update')->post('/bulkEdit', function (Request $request) {
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
        Route::middleware('permission:news_view')->get('/statistics', function () {
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
    // Applying permissions to the group is harder because it's already defined.
    // I will apply middleware to individual routes inside or wrap the group?
    // It's already wrapped in auth:sanctum.
    // store/{id?} -> news_create/update.
    // delete/{id} -> news_delete.
    // bulkEdit -> news_update.
    // statistics -> news_view.
    
    // I will modify the routes inside.
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
            $category->slug = $request->input('slug') ?: Str::slug($request->input('name'));
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
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->content,
                    'content' => $category->content,
                    'image_id' => $category->image_id,
                    'status' => $category->status,
                ],
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
            $tag->slug = $request->input('slug') ?: Str::slug($request->input('name'));
            $tag->content = $request->input('content');
            $tag->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'content' => $tag->content,
                ],
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
