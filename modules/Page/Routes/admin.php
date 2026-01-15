<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 7/1/2019
 * Time: 10:02 AM
 */
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Modules\Media\Models\MediaFile;

Route::get('/','PageController@index')->name('page.admin.index');

Route::match(['get'],'/create','PageController@create')->name('page.admin.create');

// route for edit page
Route::get('/edit/{id}', function ($id) {
    try {
        $page = Page::with('author')->findOrFail($id);

        // Resolve Image URLs
        $featuredImageUrl = $page->image_id ? '/storage/' . MediaFile::find($page->image_id)?->file_path : null;
        $bannerImageUrl = $page->banner_image_id ? '/storage/' . MediaFile::find($page->banner_image_id)?->file_path : null;
        $ogImageUrl = $page->og_image_id ? '/storage/' . MediaFile::find($page->og_image_id)?->file_path : null;
        $twitterImageUrl = $page->twitter_image_id ? '/storage/' . MediaFile::find($page->twitter_image_id)?->file_path : null;

        return response()->json([
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
                'seo_title' => $page->seo_title ?? $page->meta_title,
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
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::match(['get'],'/builder/{id}','PageController@toBuilder')->name('page.admin.builder');

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
        $page->banner_title = $request->input('banner_title');

        $page->template = $request->input('template', 'default');
        $page->status = $request->input('status', 'publish');
        $page->display_order = $request->input('display_order', 0);
        
        // Booleans
        $page->show_in_menu = $request->boolean('show_in_menu');
        $page->show_in_header = $request->boolean('show_in_header');
        $page->show_in_footer = $request->boolean('show_in_footer');
        $page->is_homepage = $request->boolean('is_homepage');
        $page->header_style = $request->input('header_style', 'normal');
        
        // SEO Fields
        $page->meta_title = $request->input('meta_title') ?? $request->input('seo_title');
        $page->meta_desc = $request->input('meta_desc') ?? $request->input('seo_description');
        $page->meta_keywords = $request->input('meta_keywords');

        $page->og_title = $request->input('og_title');
        $page->og_description = $request->input('og_description');
        $page->og_image_id = $request->input('og_image_id');
        
        $page->twitter_title = $request->input('twitter_title');
        $page->twitter_description = $request->input('twitter_description');
        $page->twitter_image_id = $request->input('twitter_image_id');
        $page->twitter_card = $request->input('twitter_card');
        
        $page->canonical_url = $request->input('canonical_url');
        $page->robots_meta = $request->input('robots_meta');
        $page->schema_markup = $request->input('schema_markup');

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

Route::get('/getForSelect2','PageController@getForSelect2')->name('page.admin.getForSelect2');
Route::post('/bulkEdit','PageController@bulkEdit')->name('page.admin.bulkEdit');

// New routes for page management
Route::post('/updateOrder', 'PageController@updateOrder')->name('page.admin.updateOrder');
Route::post('/{id}/duplicate', 'PageController@duplicate')->name('page.admin.duplicate');
Route::post('/generate-slug', 'PageController@generateSlug')->name('page.admin.generateSlug');

