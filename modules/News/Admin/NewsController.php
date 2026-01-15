<?php
namespace Modules\News\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Language\Models\Language;
use Modules\News\Models\NewsTour;
use Modules\News\Models\NewsCategory;
use Modules\News\Models\News;
use Modules\News\Models\NewsTranslation;
use Modules\Tour\Models\Tour;

class NewsController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu('/admin/module/news/news');
    }

    public function index(Request $request)
    {
        $this->checkPermission('news_view');
        $dataNews = News::query()->orderBy('id', 'desc');
        $post_name = $request->query('s');
        $cate = $request->query('cate_id');
        if ($cate) {
           $dataNews->where('cat_id', $cate);
        }
        if ($post_name) {
            $dataNews->where('title', 'LIKE', '%' . $post_name . '%');
            $dataNews->orderBy('title', 'asc');
        }

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($dataNews->with("author")->with("category")->paginate(20));
        }

        $data = [
            'rows'        => $dataNews->with("author")->with("category")->paginate(20),
            'categories'  => NewsCategory::get(),
            'tours'  => Tour::get(),
            'breadcrumbs' => [
                [
                    'name' => __('News'),
                    'url'  => route('news.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ],
            "languages"=>Language::getActive(false),
            "locale"=>\App::getLocale(),
            'page_title'=>__("News Management")
        ];
        return view('News::admin.news.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('news_create');
        $row = new News();
        $row->fill([
            'status' => 'publish',
        ]);
        $data = [
            'categories'        => NewsCategory::get()->toTree(),
            'tours'  => Tour::get(),
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __('News'),
                    'url'  => route('news.admin.index')
                ],
                [
                    'name'  => __('Add News'),
                    'class' => 'active'
                ],
            ],
            'translation'=>new NewsTranslation()
        ];
        return view('News::admin.news.detail', $data);
    }

    private function getRelativePath($id) {
        if (!$id) return null;
        $url = get_file_url($id, 'full');
        if (!$url) return null;
        $path = parse_url($url, PHP_URL_PATH);
        // Ensure path starts with /storage if it starts with /uploads
        if ($path && strpos($path, '/uploads/') === 0) {
            return '/storage' . $path;
        }
        return $path;
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('news_update');

        $row = News::find($id);

        if (empty($row)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => 'News not found'], 404);
            }
            return redirect(route('news.admin.index'));
        }

        $translation = $row->translate($request->query('lang',get_main_lang()));

        if ($request->wantsJson() || $request->expectsJson()) {
            $row->load(['tags']);
            $data = $row->toArray();

            // Map image fields to what frontend expects (featured_*)
            $data['featured_image_id'] = $row->image_id;
            $data['featured_image_alt'] = $row->image_alt;
            $data['featured_image_url'] = $this->getRelativePath($row->image_id);
            
            // SEO Images
            $data['og_image_url'] = $this->getRelativePath($row->og_image_id);
            $data['twitter_image_url'] = $this->getRelativePath($row->twitter_image_id);

            // Ensure consistent Arrays for IDs
            $data['tag_ids'] = $row->tags->pluck('id')->values()->toArray();
            // related_posts is cast to array in model, so use as is or default to empty
            $data['related_posts'] = $row->related_posts ?? [];

            // Map cat_id to category_id
            $data['category_id'] = $row->cat_id;

            // Remove extra/redundant fields (Strict cleanup)
            unset($data['image_id']);
            unset($data['image_url']); 
            unset($data['image_alt']);
            unset($data['cat_id']);
            unset($data['tags']);     // Remove relations objects
            // User payload HAS category and author, so we MUST keep them
            // unset($data['category']); 
            // unset($data['author']);   
            unset($data['image_file_path']);
            unset($data['og_image_file_path']);
            unset($data['twitter_image_file_path']);
            
            // Explicitly ensure Twitter fields are present if missing from toArray
            if (!isset($data['twitter_title'])) $data['twitter_title'] = $row->twitter_title;
            if (!isset($data['twitter_description'])) $data['twitter_description'] = $row->twitter_description;
            if (!isset($data['twitter_card'])) $data['twitter_card'] = $row->twitter_card;

            // Merge translation data
            if ($translation) {
                $transData = $translation->toArray();
                $data = array_merge($data, $transData);
            }

            return response()->json([
                'data' => $data,
                'translation' => $translation
            ]);
        }

        $data = [
            'row'  => $row,
            'translation'  => $translation,
            'categories' => NewsCategory::get()->toTree(),
            'tours'  => Tour::get(),
            'tags' => $row->tags,
            'enable_multi_lang'=>true
        ];
        return view('News::admin.news.detail', $data);
    }

    public function store(Request $request, $id){
        if(is_demo_mode()){
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __("DEMO MODE: Disable update")], 403);
            }
            return redirect()->back()->with('danger',__("DEMO MODE: Disable update"));
        }
        if($id>0){
            $this->checkPermission('news_update');
            $row = News::find($id);
            if (empty($row)) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => __('News not found')], 404);
                }
                return redirect(route('news.admin.index'));
            }
        }else{
            $this->checkPermission('news_create');
            $row = new News();
            $row->status = "publish";
            // Set author to current logged in user for new posts
            $row->author_id = \Auth::id();
        }

        $row->fill($request->input());
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }
        // Allow override of author_id only if provided in request
        if($request->input('author_id')){
            $row->author_id = $request->input('author_id');
        } elseif(!$row->author_id) {
            // Fallback to current user if no author set
            $row->author_id = \Auth::id();
        }
        $res = $row->saveOriginOrTranslation($request->query('lang'),true);

        if ($res) {
            NewsTour::where('news_id', $row->id)->delete();
            $tourIds = $request->input('tour_ids');
            if (!empty($tourIds)) {
                foreach ($tourIds as $tourId) {
                    NewsTour::create([
                        'news_id' => $row->id,
                        'tour_id' => $tourId,
                    ]);
                }
            }

            if(is_default_lang($request->query('lang'))){
                $row->saveTag($request->input('tag_name'), $request->input('tag_ids'));
            }

            // Return JSON for API requests
            if ($request->wantsJson() || $request->expectsJson() || $request->is('api/*')) {
                 $row->reload(); 
                    
                // Manually constructing image paths to avoid double prefixes
                $data = $row->load(['author', 'category', 'tags'])->toArray();
                if ($row->image_id) {
                        $media = \Modules\Media\Models\MediaFile::find($row->image_id);
                        if ($media) {
                            $data['image_file_path'] = $media->file_path;
                            $data['image_url'] = get_file_url($row->image_id, 'full');
                        }
                }
                if ($row->og_image_id) {
                        $media = \Modules\Media\Models\MediaFile::find($row->og_image_id);
                        if ($media) {
                            $data['og_image_file_path'] = $media->file_path;
                            $data['og_image_url'] = get_file_url($row->og_image_id, 'full');
                        }
                }

                return response()->json([
                    'success' => true,
                    'message' => $id > 0 ? __('News updated') : __('News created'),
                    'data' => $data
                ]);
            }

            if($id > 0 ){
                return back()->with('success',  __('News updated') );
            }else{
                return redirect(route('news.admin.edit',$row->id))->with('success', __('News created') );
            }
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => false, 'message' => __('Failed to save')], 500);
        }
        return back()->with('error', __('Failed to save'));
    }

    public function bulkEdit(Request $request)
    {
        if(is_demo_mode()){
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __("DEMO MODE: Disable update")], 403);
            }
            return redirect()->back()->with('danger',__("DEMO MODE: Disable update"));
        }
        $this->checkPermission('news_update');
        $ids = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) or !is_array($ids)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __('No items selected!')], 400);
            }
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __('Please select an action!')], 400);
            }
            return redirect()->back()->with('error', __('Please select an action!'));
        }

        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = News::where("id", $id);
                if (!$this->hasPermission('news_manage_others')) {
                    $query->where("create_user", \Auth::id());
                    $this->checkPermission('news_delete');
                }
                $row = $query->first();
                if(!empty($row)){
                    $row->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = News::where("id", $id);
                if (!$this->hasPermission('news_manage_others')) {
                    $query->where("create_user", \Auth::id());
                    $this->checkPermission('news_update');
                }
                $query->update(['status' => $action]);
            }
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function trans($id,$locale){
        $row = News::find($id);

        if(empty($row)){
            return redirect()->back()->with("danger",__("News does not exists"));
        }

        $translated = News::query()->where('origin_id',$id)->where('lang',$locale)->first();
        if(!empty($translated)){
            redirect($translated->getEditUrl());
        }

        $language = Language::where('locale',$locale)->first();
        if(empty($language)){
            return redirect()->back()->with("danger",__("Language does not exists"));
        }

        $new = $row->replicate();

        if(!$row->origin_id){
            $new->origin_id = $row->id;
        }

        $new->lang = $locale;

        $new->save();


        return redirect($new->getEditUrl());
    }
}
