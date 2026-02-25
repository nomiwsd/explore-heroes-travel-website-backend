<?php

/**
 * ADMIN PAGE MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\Page;
use Illuminate\Support\Str;

// =====================================================
// PAGE MANAGEMENT
// =====================================================
Route::get('module/page/debug-locale', function () {
    return response()->json([
        'site_locale' => setting_item('site_locale'),
        'get_main_lang' => get_main_lang(),
        'app_locale' => app()->getLocale(),
        'is_default_en' => is_default_lang('en'),
        'is_default_ar' => is_default_lang('ar'),
        'is_default_empty' => is_default_lang(''),
        'is_default_null' => is_default_lang(null),
    ]);
})->middleware('auth:sanctum');

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

    })->middleware('permission:page_view');

    // Get single page for editing
    Route::get('/edit/{id}', function (Request $request, $id) {
        try {
            $page = Page::with('author')->findOrFail($id);

            // Get translation if lang param is provided
            $lang = $request->query('lang');
            $translation = null;
            if ($lang && !is_default_lang($lang)) {
                $translation = $page->translate($lang);
            }

            // Resolve Image URLs
            $featuredImageUrl = $page->image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($page->image_id)?->file_path : null;
            $bannerImageUrl = $page->banner_image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($page->banner_image_id)?->file_path : null;
            $ogImageUrl = $page->og_image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($page->og_image_id)?->file_path : null;
            $twitterImageUrl = $page->twitter_image_id ? '/storage/' . \Modules\Media\Models\MediaFile::find($page->twitter_image_id)?->file_path : null;


            $responseData = [
                'data' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content' => $page->content,
                    'short_desc' => $page->short_desc,
                    'template' => $page->template,
                    'status' => $page->status,
                    'header_style' => $page->header_style,
                    'custom_logo' => $page->custom_logo,
                    'display_order' => $page->display_order,
                    'show_in_menu' => $page->show_in_menu,
                    'show_in_header' => $page->show_in_header,
                    'show_in_footer' => $page->show_in_footer,
                    'is_homepage' => $page->is_homepage,

                    // Images
                    'image_id' => $page->image_id,
                    'featured_image_url' => $featuredImageUrl,
                    'banner_image_id' => $page->banner_image_id,
                    'banner_image_url' => $bannerImageUrl,
                    'banner_title' => $page->banner_title,

                    // SEO - Meta
                    'seo_title' => $page->seo_title ?? $page->meta_title, // Handle both key variants if needed
                    'meta_title' => $page->meta_title ?? $page->seo_title,
                    'seo_description' => $page->seo_description ?? $page->meta_desc,
                    'meta_desc' => $page->meta_desc ?? $page->seo_description,
                    'meta_keywords' => $page->meta_keywords,

                    // SEO - Social
                    'og_title' => $page->og_title,
                    'og_description' => $page->og_description,
                    'og_image_id' => $page->og_image_id,
                    'og_image_url' => $ogImageUrl,

                    'twitter_image_id' => $page->twitter_image_id,
                    'twitter_image_url' => $twitterImageUrl,
                    'twitter_title' => $page->twitter_title,
                    'twitter_description' => $page->twitter_description,
                    'twitter_card' => $page->twitter_card,

                    'canonical_url' => $page->canonical_url,
                    'robots_meta' => $page->robots_meta,
                    'schema_markup' => $page->schema_markup,

                    'author' => $page->author,
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at,
                ],
            ];

            if ($translation) {
                $responseData['translation'] = $translation;
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    })->middleware('permission:page_view');

    // Store/Update page
    Route::middleware(['permission:page_create'])->post('/store/{id?}', function (Request $request, $id = null) {
        try {
            $lang = $request->query('lang') ?: $request->input('lang');
            $is_default = is_default_lang($lang);
            $isTranslation = $lang && !$is_default;

            // DEBUG: Write to file directly
            $debugData = [
                'time' => date('Y-m-d H:i:s'),
                'id' => $id,
                'query_lang' => $request->query('lang'),
                'input_lang' => $request->input('lang'),
                'detected_lang' => $lang,
                'is_default' => $is_default,
                'isTranslation' => $isTranslation,
                'will_save_main_table' => !$isTranslation,
            ];
            file_put_contents(storage_path('logs/page_debug.log'), json_encode($debugData) . "\n", FILE_APPEND);

            if ($id) {
                $page = Page::findOrFail($id);
            } else {
                $page = new Page();
                $page->create_user = auth()->id();
            }

            $allowedFields = [
                'title',
                'slug',
                'content',
                'status',
                'short_desc',
                'image_id',
                'header_style',
                'custom_logo',
                'banner_title',
                'banner_image_id',
                'display_order',
                'show_in_menu',
                'show_in_header',
                'show_in_footer',
                'is_homepage',
                'template_id',
                'template',
                // SEO Meta Fields
                'meta_title',
                'meta_desc',
                'meta_keywords',
                // SEO Fields
                'og_title',
                'og_description',
                'og_image_id',
                'og_image_url',
                'twitter_card',
                'twitter_title',
                'twitter_description',
                'twitter_image_id',
                'twitter_image_url',
                'canonical_url',
                'robots_meta',
                'schema_markup'
            ];

            if (!$isTranslation) {
                // For main language, fill all global fields
                $page->fill($request->only($allowedFields));

                // Handle booleans explicitly if needed
                $page->show_in_menu = $request->has('show_in_menu') ? $request->boolean('show_in_menu') : $page->show_in_menu;
                $page->show_in_header = $request->has('show_in_header') ? $request->boolean('show_in_header') : $page->show_in_header;
                $page->show_in_footer = $request->has('show_in_footer') ? $request->boolean('show_in_footer') : $page->show_in_footer;
                $page->is_homepage = $request->has('is_homepage') ? $request->boolean('is_homepage') : $page->is_homepage;

                $page->update_user = auth()->id();
                $page->save();

                // Write to debug log
                file_put_contents(storage_path('logs/page_debug.log'), "SAVED MAIN TABLE\n", FILE_APPEND);
            } else {
                // Write to debug log
                file_put_contents(storage_path('logs/page_debug.log'), "SKIPPED MAIN TABLE SAVE\n", FILE_APPEND);
            }

            // Save translation (this uses request()->input() and filters by PageTranslation fillables)
            $res = $page->saveTranslation($lang ?: get_main_lang(), true);

            return response()->json([
                'success' => true,
                'data' => ['id' => $page->id],
                'message' => $isTranslation ? 'Translation saved successfully' : 'Page saved successfully',
                'debug' => [
                    'lang' => $lang,
                    'isTranslation' => $isTranslation,
                    'mainTableSaved' => !$isTranslation,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Page store error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Delete page
    Route::middleware(['permission:page_delete'])->delete('/{id}', function ($id) {
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
    Route::middleware(['permission:page_update'])->post('/bulkEdit', function (Request $request) {
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
