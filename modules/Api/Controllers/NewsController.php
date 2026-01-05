<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;
use Modules\Location\Models\Location;

class NewsController extends Controller
{

    public function search(Request $request){
        $model_News = News::query()->select("core_news.*");
        $model_News->where("core_news.status", "publish")->orderBy('core_news.id', 'desc');
        
        // Search by keyword
        if (!empty($search = $request->query("s"))) {
            $model_News->where(function($query) use ($search) {
                $query->where('core_news.title', 'LIKE', '%' . $search . '%');
                $query->orWhere('core_news.content', 'LIKE', '%' . $search . '%');
            });

            if( setting_item('site_enable_multi_lang') && setting_item('site_locale') != app_get_locale() ){
                $model_News->leftJoin('core_news_translations', function ($join) use ($search) {
                    $join->on('core_news.id', '=', 'core_news_translations.origin_id');
                });
                $model_News->orWhere(function($query) use ($search) {
                    $query->where('core_news_translations.title', 'LIKE', '%' . $search . '%');
                    $query->orWhere('core_news_translations.content', 'LIKE', '%' . $search . '%');
                });
            }
        }
        
        // Filter by category
        if($cat_id = $request->query('cat_id')){
            $model_News->where('cat_id', $cat_id);
        }
        
        // Filter by location/destination
        if($location_id = $request->query('location_id')){
            $model_News->where('location_id', $location_id);
        }
        
        // Filter by featured
        if($request->has('is_featured') && $request->query('is_featured') !== null){
            $model_News->where('is_featured', $request->query('is_featured'));
        }
        
        // Filter by multiple categories
        if($cat_ids = $request->query('cat_ids')){
            $catIdsArray = is_array($cat_ids) ? $cat_ids : explode(',', $cat_ids);
            $model_News->whereIn('cat_id', $catIdsArray);
        }
        
        // Filter by multiple locations
        if($location_ids = $request->query('location_ids')){
            $locationIdsArray = is_array($location_ids) ? $location_ids : explode(',', $location_ids);
            $model_News->whereIn('location_id', $locationIdsArray);
        }
        
        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');
        $allowedSorts = ['created_at', 'title', 'id'];
        if (in_array($sortBy, $allowedSorts)) {
            $model_News->orderBy('core_news.' . $sortBy, $sortOrder);
        }
        
        $perPage = $request->query('per_page', 10);
        $rows = $model_News->with("author")->with('translation')->with("category")->with("location")->paginate($perPage);
        $total = $rows->total();
        
        return $this->sendSuccess(
            [
                'total' => $total,
                'total_pages' => $rows->lastPage(),
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'data' => $rows->map(function($row){
                    return $row->dataForApi();
                }),
            ]
        );
    }
    
    public function featured(Request $request){
        $limit = $request->query('limit', 6);
        $rows = News::getFeatured($limit);
        
        return $this->sendSuccess(
            [
                'total' => $rows->count(),
                'data' => $rows->map(function($row){
                    return $row->dataForApi();
                }),
            ]
        );
    }
    
    public function category(Request $request){
        $model_News = NewsCategory::query()->select("core_news_category.*");
        $model_News->where("core_news_category.status", "publish");
        if (!empty($search = $request->query("s"))) {
            $model_News->where(function($query) use ($search) {
                $query->where('core_news_category.name', 'LIKE', '%' . $search . '%');
            });

            if( setting_item('site_enable_multi_lang') && setting_item('site_locale') != app_get_locale() ){
                $model_News->leftJoin('core_news_category_translations', function ($join) use ($search) {
                    $join->on('core_news_category.id', '=', 'core_news_category_translations.origin_id');
                });
                $model_News->orWhere(function($query) use ($search) {
                    $query->where('core_news_category_translations.title', 'LIKE', '%' . $search . '%');
                });
            }
        }
        $rows = $model_News->with('translation')->get()->toTree();
        return $this->sendSuccess(
            [
                'data'=>$rows->map(function($row){
                    return $row->dataForApi();
                }),
            ]
        );
    }

    public function detail($id = '')
    {
        // Try to find by ID first, then by slug
        $row = is_numeric($id) ? News::find($id) : News::where('slug', $id)->first();
        
        if(empty($row) || $row->status !== 'publish'){
            return $this->sendError(__("News not found"));
        }
        return $this->sendSuccess([
            'data' => $row->dataForApi(true)
        ]);
    }
    
    public function locations(Request $request){
        // Get locations that have news posts
        $locations = Location::query()
            ->select('core_locations.id', 'core_locations.name', 'core_locations.slug')
            ->join('core_news', 'core_locations.id', '=', 'core_news.location_id')
            ->where('core_news.status', 'publish')
            ->groupBy('core_locations.id', 'core_locations.name', 'core_locations.slug')
            ->get();
            
        return $this->sendSuccess([
            'data' => $locations
        ]);
    }
    
    public function allLocations(Request $request){
        // Get all locations for filter dropdown
        $locations = Location::query()
            ->select('id', 'name', 'slug')
            ->where('status', 'publish')
            ->orderBy('name')
            ->get();
            
        return $this->sendSuccess([
            'data' => $locations
        ]);
    }
}
