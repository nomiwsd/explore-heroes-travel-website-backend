<?php
namespace Modules\Page\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\AdminController;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;

class PageController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        $data = [
            'rows' => Page::paginate(20)
        ];
        return view('Page::frontend.index', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function detail()
    {
        /**
         * @var Page $page
         * @var PageTranslation $translation
         */
        $slug = request()->route('slug');

        $page = Page::where('slug', $slug)->first();

        if (empty($page) || !$page->is_published) {
            abort(404);
        }
        $translation = $page->translate();
        $data = [
            'row' => $page,
            'translation' => $translation,
            'seo_meta'  => $page->getSeoMetaWithTranslation(app()->getLocale(),$translation),
            'body_class'  => "page",
        ];
        if(!empty($page->header_style) and $page->header_style == "transparent"){
            $data['header_transparent'] = true;
        }
        return view('Page::frontend.detail', $data);
    }
    public function apiDetail($slug)
    {
        $page = Page::where('slug', $slug)->first();

        if (empty($page) || $page->status != 'publish') {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Add author and banner_image relationships if needed
        $page->load(['author', 'banner_image', 'image']);

        return response()->json($page);
    }

    public function homepage()
    {
        $page = Page::where('is_homepage', true)->where('status', 'publish')->first();

        if (empty($page)) {
            // Fallback to slug 'home' if no specific homepage is set
            $page = Page::where('slug', 'home')->where('status', 'publish')->first();
        }

        if (empty($page)) {
             return response()->json(['message' => 'Homepage not found'], 404);
        }

        $page->load(['author', 'banner_image', 'image']);

        return response()->json($page);
    }

    public function menuPages()
    {
        $location = request()->get('location', 'menu');
        
        $query = Page::where('status', 'publish');

        if ($location === 'header') {
            $query->where('show_in_header', true);
        } elseif ($location === 'footer') {
            $query->where('show_in_footer', true);
        } else {
             $query->where('show_in_menu', true);
        }

        $pages = $query->orderBy('display_order', 'asc')->get();

        return response()->json(['data' => $pages]);
    }
}
