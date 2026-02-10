<?php
namespace Modules\Location\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationTranslation;

class LocationController extends AdminController
{
    private Location $location;

    public function __construct(Location $location)
    {
        $this->setActiveMenu('/admin/module/location/location');
        $this->location = $location;
    }

    /**
     * Skip permission check for API requests (frontend uses its own auth)
     */
    private function apiCheckPermission($permission)
    {
        if (request()->wantsJson() || request()->expectsJson()) {
            return; // Skip for API requests
        }
        $this->checkPermission($permission);
    }

    public function index(Request $request)
    {
        $this->apiCheckPermission('location_view');
        $listLocation = $this->location::query() ;
        if (!empty($search = $request->query('s'))) {
            $listLocation->where('name', 'LIKE', '%' . $search . '%');
        }
        $listLocation->orderBy('created_at', 'asc');

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($listLocation->paginate(20));
        }

        $data = [
            'rows'        => $listLocation->get()->toTree(),
            'row'         => $this->location,
            'translation' => new ($this->location->getTranslationModelName()),
            'breadcrumbs' => [
                [
                    'name' => __('Location'),
                    'url'  => route('location.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Location::admin.index', $data);
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
        $this->apiCheckPermission('location_update');
        $row = $this->location::find($id);
        
        if (empty($row)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => 'Location not found'], 404);
            }
            return redirect(route('location.admin.index'));
        }
        
        $translation = $row->translate($request->query('lang',get_main_lang()));

        if ($request->wantsJson() || $request->expectsJson()) {
            $imageUrl = $this->getRelativePath($row->image_id);
            $bannerImageUrl = $this->getRelativePath($row->banner_image_id);
            $ogImageUrl = $this->getRelativePath($row->og_image_id);
            $twitterImageUrl = $this->getRelativePath($row->twitter_image_id);
            $bannerImageUrl = $this->getRelativePath($row->banner_image_id);
            $ogImageUrl = $this->getRelativePath($row->og_image_id);
            $twitterImageUrl = $this->getRelativePath($row->twitter_image_id);

            $seo = $row->getSeoMeta();

            return response()->json([
                'data' => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'content' => $translation->content,
                    'banner_image_id' => $row->banner_image_id ?? null,
                    'banner_image_url' => $bannerImageUrl,
                    'gallery' => $row->gallery,
                    'map_lat' => $row->map_lat,
                    'map_lng' => $row->map_lng,
                    'map_zoom' => $row->map_zoom,
                    'status' => $row->status,
                    'parent_id' => $row->parent_id,
                    'is_featured' => $row->is_featured,
                    'show_on_homepage' => $row->show_on_homepage,
                    'destination_type' => $row->destination_type ?? 'city',
                    'display_order' => $row->display_order ?? 0,
                    'short_description' => $translation->short_description,
                    'seo_title' => $seo['seo_title'] ?? '',
                    'seo_desc' => $seo['seo_desc'] ?? '',
                    'og_title' => $row->og_title,
                    'og_description' => $row->og_description,
                    'og_image_id' => $row->og_image_id,
                    'og_image_url' => $ogImageUrl,
                    'twitter_card' => $row->twitter_card,
                    'twitter_title' => $row->twitter_title,
                    'twitter_description' => $row->twitter_description,
                    'twitter_image_id' => $row->twitter_image_id,
                    'twitter_image_url' => $twitterImageUrl,
                    'canonical_url' => $row->canonical_url,
                    'robots_meta' => $row->robots_meta,
                    'schema_markup' => $row->schema_markup,
                    'tours' => $row->tours->map(function($tl){
                        return ['id' => $tl->tour_id];
                    }),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'author' => $row->author ? ['display_name' => $row->author->getDisplayName()] : null,
                    'seo_keywords' => $row->seo_keywords,

                ],
                'translation' => $translation
            ]);
        }
        
        $data = [
            'translation' => $translation,
            'enable_multi_lang'=>true,
            'row'         => $row,
            'parents'     => $this->location::get()->toTree(),
            'breadcrumbs' => [
                [
                    'name' => __('Location'),
                    'url'  => route('location.admin.index')
                ],
                [
                    'name'  => __('Edit'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Location::admin.detail', $data);
    }

    public function store( Request $request, $id = null ){
        if(is_demo_mode()){
            return redirect()->back()->with('danger',__("DEMO MODE: can not add data"));
        }
        $this->apiCheckPermission('location_update');

        if($id>0){
            $row = $this->location::find($id);
            if (empty($row)) {
                return redirect(route('location.admin.index'));
            }
        }else{
            $row = $this->location;
            $row->status = "publish";
        }

        $record = $row->fill($request->input());
        $row->map_lat = $request->input('map_lat');
        $row->map_lng = $request->input('map_lng');
        $row->map_zoom = $request->input('map_zoom');
        $row->trip_ideas = $request->input('trip_ideas');
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }
        $row->gallery = $request->input('gallery');
        
        do_action(\Modules\Location\Hook::BEFORE_SAVING,$row,$request);
        $res = $row->saveOriginOrTranslation($request->input('lang'),true);
        if ($res) {
            // Use updateOrInsert to avoid duplicate entry errors
            $locale = $request->input('lang') ?? get_main_lang() ?? 'en';
            LocationTranslation::updateOrInsert(
                ['origin_id' => $row->id, 'locale' => $locale],
                [
                    'name' => $request->input('name'),
                    'short_description' => $request->input('short_description'),
                    'content' => $request->input('content'),
                    'trip_ideas' => $request->input('trip_ideas'),
                    'create_user' => Auth::id(),
                    'updated_at' => now(),
                ]
            );
            
            // Save SEO
            $row->saveSEO($request);

            // Save Tours
            $tours = $request->input('assigned_tour_ids');
            if (is_array($tours)) {
                \Modules\Tour\Models\TourLocation::where('location_id', $row->id)->delete();
                foreach ($tours as $tour_id) {
                    $tl = new \Modules\Tour\Models\TourLocation();
                    $tl->location_id = $row->id;
                    $tl->tour_id = $tour_id;
                    $tl->save();
                }
            }

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $id > 0 ? __('Location updated') : __('Location created'),
                    'data' => ['id' => $row->id]
                ]);
            }

            if($id > 0 ){
                return back()->with('success',  __('Location updated') );
            }else{
                return redirect(route('location.admin.index',$row->id))->with('success', __('Location created') );
            }
        }
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            if(is_array($selected))
            {
                $items = $this->location::select('id', 'name as text')->whereIn('id',$selected)->take(50)->get();
                return response()->json([
                    'items'=>$items
                ]);
            }else{
                $items = $this->location::find($selected);
            }

            return [
                'results'=>$items
            ];
        }

        $q = $request->query('q');
        $query = $this->location::select('id', 'name as text')->where("status","publish");
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => __("Select at least 1 item!")], 422);
            }
            return redirect()->back()->with('error', __("Select at least 1 item!"));
        }
        if (empty($action)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => __('Select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Select an Action!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->location::where("id", $id);
                // For API requests (frontend admin), skip ownership check
                // For web requests, check ownership if user can't manage others
                $isApiRequest = $request->wantsJson() || $request->expectsJson();
                if (!$isApiRequest && !$this->hasPermission('location_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->apiCheckPermission('location_delete');
                }
                $row = $query->first();
                if(!empty($row)){
                    //Sync child location
                    $list_childs = $this->location::where("parent_id", $id)->get();
                    if(!empty($list_childs)){
                        foreach ($list_childs as $child){
                            $child->parent_id = null;
                            $child->save();
                        }
                    }
                    //Del parent location
                    $row->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = $this->location::where("id", $id);
                if (!$this->hasPermission('location_manage_others')) {
                    $query->where("create_user", Auth::id());
                    $this->apiCheckPermission('location_update');
                }
                $query->update(['status' => $action]);
            }
        }
        
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Updated success!')
            ]);
        }
        
        return redirect()->back()->with('success', __('Updated success!'));
    }
}
