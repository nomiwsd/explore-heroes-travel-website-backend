<?php

namespace Modules\Page\Admin;

use Modules\Page\Hook;
use function Couchbase\defaultDecoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;
use Modules\Template\Models\Template;

class PageController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu('/admin/module/page/page');
    }

    public function index(Request $request)
    {
        $this->checkPermission('page_view');
        $page_name = $request->query('page_name');
        $datapage = new Page();
        if ($page_name) {
            $datapage = Page::where('title', 'LIKE', '%' . $page_name . '%');
        }
        $datapage = $datapage->with(['author'])->orderBy('title', 'asc');

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($datapage->paginate(20));
        }

        $data = [
            'rows' => $datapage->paginate(20),
            'page_title' => __("Page Management"),
            'breadcrumbs' => [
                [
                    'name' => __('Pages'),
                    'url' => route('page.admin.index')
                ],
                [
                    'name' => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Page::admin.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('page_create');
        $row = new Page();
        $row->fill([
            'status' => 'publish',
        ]);

        $data = [
            'row' => $row,
            'translation' => new PageTranslation(),
            'templates' => Template::orderBy('id', 'desc')->limit(100)->get(),
            'breadcrumbs' => [
                [
                    'name' => __('Pages'),
                    'url' => route('page.admin.index')
                ],
                [
                    'name' => __('Add Page'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Page::admin.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('page_update');
        $row = Page::find($id);

        if (empty($row)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => 'Page not found'], 404);
            }
            return redirect(route('page.admin.index'));
        }
        $translation = $row->translate($request->query('lang', get_main_lang()));

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            // Load relationships
            $row->load(['banner_image', 'image', 'author']);
            
            // Build explicit response with all fields
            $data = [
                'id' => $row->id,
                'slug' => $row->slug,
                'title' => $row->title,
                'meta_title' => $row->meta_title ?? '',
                'meta_desc' => $row->meta_desc ?? '',
                'meta_keywords' => $row->meta_keywords ?? '',
                'og_title' => $row->og_title,
                'og_description' => $row->og_description,
                'og_image_id' => $row->og_image_id,
                'twitter_card' => $row->twitter_card,
                'twitter_title' => $row->twitter_title,
                'twitter_description' => $row->twitter_description,
                'twitter_image_id' => $row->twitter_image_id,
                'canonical_url' => $row->canonical_url,
                'robots_meta' => $row->robots_meta,
                'schema_markup' => $row->schema_markup,
                'content' => $row->content ?? '',
                'short_desc' => $row->short_desc ?? '',
                'status' => $row->status ?? 'draft',
                'is_homepage' => (bool) $row->is_homepage,
                'image_id' => $row->image_id,
                'template_id' => $row->template_id,
                'template' => $row->template ?? 'default',
                'header_style' => $row->header_style ?? 'normal',
                'custom_logo' => $row->custom_logo ?? '',
                'banner_title' => $row->banner_title ?? '',
                'banner_image_id' => $row->banner_image_id,
                'display_order' => (int) ($row->display_order ?? 0),
                'show_in_menu' => (bool) $row->show_in_menu,
                'show_in_header' => (bool) $row->show_in_header,
                'show_in_footer' => (bool) $row->show_in_footer,
                'create_user' => $row->create_user,
                'update_user' => $row->update_user,
                'deleted_at' => $row->deleted_at,
                'origin_id' => $row->origin_id,
                'lang' => $row->lang,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            // Add banner image 
            if ($row->banner_image) {
                $data['banner_image'] = [
                    'id' => $row->banner_image->id,
                    'file_path' => $row->banner_image->file_path,
                    'url' => get_file_url($row->banner_image->file_path, 'full'),
                    'alt_text' => $row->banner_image->alt_text ?? '',
                ];
            } else {
                $data['banner_image'] = null;
            }
            
            // Add image
            if ($row->image) {
                $data['image'] = [
                    'id' => $row->image->id,
                    'file_path' => $row->image->file_path,
                    'url' => get_file_url($row->image->file_path, 'full'),
                    'alt_text' => $row->image->alt_text ?? '',
                ];
            } else {
                $data['image'] = null;
            }
            
            // Add author
            if ($row->author) {
                $data['author'] = [
                    'id' => $row->author->id,
                    'name' => $row->author->name,
                ];
            } else {
                $data['author'] = null;
            }
            
            return response()->json([
                'data' => $data,
                'translation' => $translation
            ]);
        }

        $data = [
            'translation' => $translation,
            'row' => $row,
            'templates' => Template::orderBy('id', 'desc')->limit(100)->get(),
            'breadcrumbs' => [
                [
                    'name' => __('Pages'),
                    'url' => route('page.admin.index')
                ],
                [
                    'name' => __('Edit Page'),
                    'class' => 'active'
                ],
            ],
            'enable_multi_lang' => true
        ];
        return view('Page::admin.detail', $data);
    }

    public function store(Request $request, $id = -1)
    {
        \Illuminate\Support\Facades\Log::info('Page Store Request Data:', $request->all());

        if (is_demo_mode()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __("DEMO MODE: Disable update")], 403);
            }
            return redirect()->back()->with('danger', __("DEMO MODE: Disable update"));
        }
        $request->validate([
            'title' => 'required'
        ]);

        if ($id > 0) {
            $this->checkPermission('page_update');
            $row = Page::find($id);
            if (empty($row)) {
                return redirect(route('page.admin.index'));
            }
        } else {
            $this->checkPermission('page_create');
            $row = new Page();
        }

        $row->fill($request->input());
        if ($request->input('slug')) {
            $row->slug = $request->input('slug');
        }

        $row->save();

        do_action(Hook::PAGE_BEFORE_SAVING, $row, $request);
        $row->saveOriginOrTranslation($request->query('lang'), true);

        if ($request->wantsJson()) {
            return response()->json($row);
        }

        if ($id > 0) {
            return back()->with('success', __('Page updated'));
        } else {
            return redirect()->route('page.admin.edit', ['id' => $row->id])->with('success', $id > 0 ? __('Page updated') : __('Page created'));
        }
    }

    public function getForSelect2(Request $request)
    {
        $q = $request->query('q');
        $query = Page::select('id', 'title as text');
        if ($q) {
            $query->where('title', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

    public function bulkEdit(Request $request)
    {
        if (is_demo_mode()) {
            if ($request->wantsJson()) {
                 return response()->json(['error' => __("DEMO MODE: Disable update")], 403);
            }
            return redirect()->back()->with('danger', __("DEMO MODE: Disable update"));
        }
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids)) {
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('No Action is selected!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Page::where("id", $id);
                if (!$this->hasPermission('page_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->checkPermission('page_delete');
                }
                $query->first();
                if (!empty($query)) {
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = Page::where("id", $id);
                if (!$this->hasPermission('page_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->checkPermission('page_update');
                }
                $query->update(['status' => $action]);
            }
        }
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }


    public function toBuilder($id)
    {
        $row = Page::find($id);

        if (empty($row)) {
            return redirect(route('page.admin.index'));
        }
        $temp = $row->template;
        if (!$row->template) {
            $temp = new Template(
                [
                    'title' => $row->title
                ]
            );
            $temp->save();
            $row->template_id = $temp->id;
        }
        $row->show_template = 1;
        $row->save();

        return redirect(route('template.admin.live.index', ['template' => $temp, 'ref' => 'page', 'refId' => $id]));
    }

    /**
     * Update page order (for drag & drop)
     */
    public function updateOrder(Request $request)
    {
        $this->checkPermission('page_update');
        
        $items = $request->input('items', []);
        
        foreach ($items as $item) {
            Page::where('id', $item['id'])->update(['display_order' => $item['position']]);
        }
        
        return response()->json(['success' => true, 'message' => 'Order updated']);
    }

    /**
     * Duplicate a page
     */
    public function duplicate(Request $request, $id)
    {
        $this->checkPermission('page_create');
        
        $original = Page::find($id);
        if (!$original) {
            return response()->json(['error' => 'Page not found'], 404);
        }
        
        $newPage = $original->replicate();
        $newPage->title = $original->title . ' (Copy)';
        $newPage->slug = $original->slug . '-copy-' . time();
        $newPage->is_homepage = false; // Don't duplicate homepage status
        $newPage->status = 'draft';
        $newPage->save();
        
        return response()->json([
            'success' => true, 
            'message' => 'Page duplicated',
            'data' => $newPage
        ]);
    }

    /**
     * Generate slug from title
     */
    public function generateSlug(Request $request)
    {
        $title = $request->input('title', '');
        $slug = \Illuminate\Support\Str::slug($title);
        
        // Check uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return response()->json(['slug' => $slug]);
    }

    /**
     * API: Get page by slug (public)
     */
    public function apiDetail($slug)
    {
        $page = Page::where('slug', $slug)->published()->first();
        
        if (!$page) {
            // Check for homepage
            if ($slug === 'home' || $slug === '/') {
                $page = Page::homepage()->published()->first();
            }
        }
        
        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }
        
        // Load relationships
        $page->load(['banner_image', 'image']);
        
        // Format response
        $data = $page->toArray();
        if ($page->banner_image) {
            $data['banner_image'] = [
                'id' => $page->banner_image->id,
                'file_path' => $page->banner_image->file_path,
                'url' => get_file_url($page->banner_image->file_path, 'full'),
                'alt_text' => $page->banner_image->alt_text ?? '',
            ];
        }
        if ($page->image) {
            $data['image'] = [
                'id' => $page->image->id,
                'file_path' => $page->image->file_path,
                'url' => get_file_url($page->image->file_path, 'full'),
                'alt_text' => $page->image->alt_text ?? '',
            ];
        }
        
        return response()->json($data);
    }

    /**
     * API: Get menu pages (for header/footer)
     */
    public function menuPages(Request $request)
    {
        $location = $request->query('location', 'menu'); // menu, header, footer
        
        $query = Page::published();
        
        if ($location === 'header') {
            $query->headerPages();
        } elseif ($location === 'footer') {
            $query->footerPages();
        } else {
            $query->menuPages();
        }
        
        $pages = $query->select('id', 'title', 'slug', 'display_order')->get();
        
        return response()->json(['data' => $pages]);
    }

    /**
     * API: Get homepage
     */
    public function homepage()
    {
        $page = Page::homepage()->published()->first();
        
        if (!$page) {
            return response()->json(['error' => 'Homepage not configured'], 404);
        }
        
        // Load relationships
        $page->load(['banner_image', 'image']);
        
        // Format response
        $data = $page->toArray();
        if ($page->banner_image) {
            $data['banner_image'] = [
                'id' => $page->banner_image->id,
                'file_path' => $page->banner_image->file_path,
                'url' => get_file_url($page->banner_image->file_path, 'full'),
                'alt_text' => $page->banner_image->alt_text ?? '',
            ];
        }
        
        return response()->json($data);
    }
}
