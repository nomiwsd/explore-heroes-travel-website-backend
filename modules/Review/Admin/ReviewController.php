<?php
namespace Modules\Review\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\AdminController;
use Modules\Review\Models\Review;
use Modules\Review\Models\ReviewMeta;

class ReviewController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu('/admin/module/review/review');
    }

    public function index(Request $request)
    {
        $this->checkPermission("review_manage_others");
        $model = Review::query();
        $model->orderBy('id', 'desc');
        if (!empty($author = $request->input('customer_id'))) {
            $model->where('author_id', $author);
        }
        $allServices = get_reviewable_services();
        $allServicesKeys = array_keys($allServices);

        if (!empty($search_name = $request->input('s'))) {
            $search_name = "%".$search_name."%";
            $model->whereRaw(" ( title LIKE ? OR author_ip LIKE ? OR content LIKE ? ) ",[$search_name,$search_name,$search_name]);
            $model->orderBy('title', 'asc');
        }
        if (!empty($status = $request->input('status'))) {
            $model->where('status', $status);
        }
        if (!empty($service_type = $request->input('service'))) {
            $model->where('object_model', $service_type);
        }
        if (!empty($service_id = $request->input('service_id'))) {
            $model->where('object_id', $service_id);
        }
        if (!empty($object_model = $request->input('object_model')) and in_array($object_model,$allServicesKeys)) {
            $model->where('object_model', $object_model );
        }
        $model->whereIn('object_model', $allServicesKeys );

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            $reviews = $model->with(['author'])->paginate(20);
            return response()->json($reviews);
        }

        $data = [
            'rows'        => $model->paginate(10),
            'breadcrumbs' => [
                ['name'  => __('Review'),
                 'class' => 'active'
                ],
            ]
        ];
        return view('Review::admin.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission("review_manage_others");
        $review = Review::with(['author', 'getService'])->findOrFail($id);
        
        // Get review meta
        $meta = ReviewMeta::where('review_id', $id)->get();
        $reviewData = $review->toArray();
        $reviewData['meta'] = $meta;
        
        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($reviewData);
        }
        
        return view('Review::admin.edit', ['row' => $review]);
    }

    public function store(Request $request, $id = null)
    {
        $this->checkPermission("review_manage_others");
        
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|numeric|min:1|max:5',
            'author_name' => 'required|string|max:255',
        ]);
        
        if ($id) {
            $review = Review::findOrFail($id);
        } else {
            $review = new Review();
            $review->author_id = auth()->id();
        }
        
        // Basic fields
        $review->title = $request->input('title');
        $review->content = $request->input('content');
        $review->rate_number = $request->input('rating');
        $review->status = $request->input('status', 'approved');
        
        // Object association
        if ($request->has('tour_id') && $request->input('tour_id')) {
            $review->object_id = $request->input('tour_id');
            $review->object_model = 'tour';
        } elseif ($request->has('object_id')) {
            $review->object_id = $request->input('object_id');
            $review->object_model = $request->input('object_model', 'tour');
        }
        
        // Save the review
        $review->save();
        
        // Save meta fields
        $metaFields = [
            'author_name' => $request->input('author_name'),
            'author_email' => $request->input('author_email'),
            'author_avatar' => $request->input('author_avatar'),
            'author_location' => $request->input('author_location'),
            'author_country' => $request->input('author_country'),
            'review_source' => $request->input('review_source'),
            'review_date' => $request->input('review_date'),
            'show_on_homepage' => $request->input('show_on_homepage') ? '1' : '0',
            'show_on_tour_page' => $request->input('show_on_tour_page') ? '1' : '0',
            'is_featured' => $request->input('is_featured') ? '1' : '0',
            'agent_id' => $request->input('agent_id'),
            'agent_name' => $request->input('agent_name'),
            'agent_role' => $request->input('agent_role'),
            'agent_photo' => $request->input('agent_photo'),
            'trip_summary' => $request->input('trip_summary'),
        ];
        
        foreach ($metaFields as $key => $value) {
            if ($value !== null) {
                $review->addMeta($key, $value, false);
            }
        }
        
        // Save review images
        if ($request->has('images') && is_array($request->input('images'))) {
            // Remove old images
            ReviewMeta::where('review_id', $review->id)->where('name', 'review_image')->delete();
            // Add new images
            foreach ($request->input('images') as $imageId) {
                $review->addMeta('review_image', $imageId, true);
            }
        }
        
        // Save category ratings
        if ($request->has('meta') && is_array($request->input('meta'))) {
            foreach ($request->input('meta') as $metaItem) {
                if (isset($metaItem['name']) && isset($metaItem['val'])) {
                    $review->addMeta($metaItem['name'], $metaItem['val'], false);
                }
            }
        }
        
        // Clear cache
        if ($review->object_id && $review->object_model) {
            Cache::forget("review_" . $review->object_model . "_" . $review->object_id);
        }
        
        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $id ? 'Review updated successfully' : 'Review created successfully',
                'data' => $review
            ]);
        }
        
        return redirect()->route('review.admin.index')->with('success', $id ? 'Review updated!' : 'Review created!');
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission("review_manage_others");
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No items selected!'], 400);
            }
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please select an action!'], 400);
            }
            return redirect()->back()->with('error', __('Please select an action!'));
        }
        $allServices = get_bookable_services();
        if ($action == "delete") {
            foreach ($ids as $id) {
                $review = Review::where('id', $id)->first();
                if(!empty($review)){
                    $review->delete();
                    $review->save();
                    $module_class = $allServices[$review->object_model] ?? false;
                    if(!empty($module_class)){
                        $model_serivce = $module_class::withTrashed()->find($review->object_id);
                        if(!empty($model_serivce)){
                            Cache::forget('review_' . $model_serivce->type . '_' . $review->object_id);
                            $model_serivce->update_service_rate();
                        }
                    }
                }
            }
        } else {
            foreach ($ids as $id) {
                $review = Review::where('id', $id)->first();
                $review->status = $action;
                $review->save();
                $module_class = $allServices[$review->object_model] ?? false;
                if(!empty($module_class)){
                    $model_serivce = $module_class::withTrashed()->find($review->object_id);
                    if(!empty($model_serivce)){
                        Cache::forget('review_' . $model_serivce->type . '_' . $review->object_id);
                        $model_serivce->update_service_rate();
                    }
                }
            }
        }
        
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Update success!']);
        }
        
        return redirect()->back()->with('success', __('Update success!'));
    }
}
