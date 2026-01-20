<?php
namespace Modules\Review\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\AdminController;
use Modules\Review\Models\Review;
use Modules\Review\Models\ReviewMeta;
use Modules\Media\Models\MediaFile;
use Modules\Tour\Models\Tour;

class ReviewController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu('/admin/module/review/review');
    }

    private function transformReview($review)
    {
        // Get Tour data if applicable
        $tour = null;
        if ($review->object_model === 'tour' && $review->object_id) {
            $tourObj = Tour::find($review->object_id);
            if ($tourObj) {
                $tour = [
                    'id' => $tourObj->id,
                    'title' => $tourObj->title,
                    'slug' => $tourObj->slug,
                    'price' => $tourObj->price,
                    'image_id' => $tourObj->image_id,
                ];
            }
        }

        // Get Images
        $images = [];
        $meta = ReviewMeta::where('review_id', $review->id)->get();
        $imageIds = [];
        foreach ($meta as $m) {
            if ($m->name === 'review_image') {
                $imageIds[] = (int) $m->val;
            }
        }

        if (!empty($imageIds)) {
            $mediaFiles = MediaFile::whereIn('id', $imageIds)->get();
            foreach ($imageIds as $imgId) {
                $media = $mediaFiles->firstWhere('id', $imgId);
                if ($media) {
                    $filePath = $media->file_path;
                    if (!str_starts_with($filePath, 'uploads/')) {
                        $filePath = 'uploads/' . $filePath;
                    }
                    $images[] = [
                        'id' => $media->id,
                        'url' => $filePath,
                        'file_path' => $media->file_path,
                    ];
                }
            }
        }

        // Get Author Avatar
        $authorAvatar = $review->author_avatar;
        if (empty($authorAvatar) && $review->author && $review->author->avatar_id) {
            $authorAvatar = get_file_url($review->author->avatar_id, 'full');
        }

        return [
            'id' => $review->id,
            'author_name' => $review->author_name ?? ($review->author ? $review->author->name : 'Anonymous'),
            'author_email' => $review->author_email ?? ($review->author ? $review->author->email : ''),
            'author_avatar' => $authorAvatar,
            'author_location' => $review->author_location,
            'author_country' => $review->author_country,
            'rating' => (float) $review->rate_number,
            'title' => $review->title,
            'content' => $review->content,
            'status' => $review->status,
            'show_on_homepage' => (bool) $review->show_on_homepage,
            'show_on_tour_page' => (bool) $review->show_on_tour_page,
            'is_featured' => (bool) $review->is_featured,
            'tour_id' => ($review->object_model === 'tour') ? $review->object_id : null,
            'review_source' => $review->review_source,
            'review_date' => $review->review_date,
            'trip_summary' => $review->trip_summary,
            'agent_name' => $review->agent_name,
            'agent_role' => $review->agent_role,
            'agent_photo' => $review->agent_photo,
            'images' => $images,
            'tour' => $tour,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];
    }

    public function index(Request $request)
    {
        if (!$this->hasPermission('review_manage_others') && !$this->hasPermission('review_manage')) {
            abort(403);
        }
        $model = Review::query();
        $model->orderBy('id', 'desc');
        if (!empty($author = $request->input('customer_id'))) {
            $model->where('author_id', $author);
        }

        $allServices = get_reviewable_services();
        $allServicesKeys = array_keys($allServices);

        if (!empty($search_name = $request->input('s'))) {
            $search_name = "%".$search_name."%";
            $model->whereRaw(" ( title LIKE ? OR content LIKE ? ) ", [$search_name, $search_name]);
            $model->orderBy('title', 'asc');
        }
        if (!empty($status = $request->input('status')) && $status !== 'all') {
            $model->where('status', $status);
        }
        if (!empty($service_type = $request->input('service')) && $service_type !== 'all') {
            $model->where('object_model', $service_type);
        }
        if (!empty($service_id = $request->input('service_id'))) {
            $model->where('object_id', $service_id);
        }
        $model->whereIn('object_model', $allServicesKeys);


        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            $reviews = $model->with(['author'])->paginate(20);

            $response = [
                'current_page' => $reviews->currentPage(),
                'data' => collect($reviews->items())->map(function ($review) {
                    return $this->transformReview($review);
                }),
                'first_page_url' => $reviews->url(1),
                'from' => $reviews->firstItem(),
                'last_page' => $reviews->lastPage(),
                'last_page_url' => $reviews->url($reviews->lastPage()),
                'next_page_url' => $reviews->nextPageUrl(),
                'path' => $reviews->path(),
                'per_page' => $reviews->perPage(),
                'prev_page_url' => $reviews->previousPageUrl(),
                'to' => $reviews->lastItem(),
                'total' => $reviews->total(),
            ];

            return response()->json($response);
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
        if (!$this->hasPermission('review_manage_others') && !$this->hasPermission('review_manage')) {
            abort(403);
        }
        $review = Review::with(['author'])->findOrFail($id);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'data' => $this->transformReview($review)
            ]);
        }
        
        return view('Review::admin.edit', ['row' => $review]);
    }

    public function store(Request $request, $id = null)
    {
        if (!$this->hasPermission('review_manage_others') && !$this->hasPermission('review_manage')) {
            abort(403);
        }
        
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

        // Main table extended fields
        $review->author_name = $request->input('author_name');
        $review->author_email = $request->input('author_email');
        $review->author_avatar = $request->input('author_avatar');
        $review->author_location = $request->input('author_location');
        $review->author_country = $request->input('author_country');
        $review->review_source = $request->input('review_source');
        $review->review_date = $request->input('review_date');
        $review->trip_summary = $request->input('trip_summary');
        $review->agent_name = $request->input('agent_name');
        $review->agent_role = $request->input('agent_role');
        $review->agent_photo = $request->input('agent_photo');

        // Booleans
        $review->is_featured = $request->input('is_featured') ? 1 : 0;
        $review->show_on_homepage = $request->input('show_on_homepage') ? 1 : 0;
        $review->show_on_tour_page = $request->input('show_on_tour_page') ? 1 : 0;

        // Object association
        if ($request->has('tour_id') && $request->input('tour_id')) {
            $review->object_id = $request->input('tour_id');
            $review->object_model = 'tour';
        } elseif ($request->has('object_id')) {
            $review->object_id = $request->input('object_id');
            $review->object_model = $request->input('object_model', 'tour');
        }

        $review->save();

        // Save images to meta
        if ($request->has('images') && is_array($request->input('images'))) {
            ReviewMeta::where('review_id', $review->id)->where('name', 'review_image')->delete();
            foreach ($request->input('images') as $imageId) {
                $review->addMeta('review_image', $imageId, true);
            }
        }

        // Clear caches
        if ($review->object_id && $review->object_model) {
            Cache::forget("review_" . $review->object_model . "_" . $review->object_id);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $id ? 'Review updated successfully' : 'Review created successfully',
                'data' => $this->transformReview($review)
            ]);
        }

        return \Illuminate\Support\Facades\Redirect::route('review.admin.index')->with('success', $id ? 'Review updated!' : 'Review created!');
    }

    public function bulkEdit(Request $request)
    {
        if (!$this->hasPermission('review_manage_others') && !$this->hasPermission('review_manage')) {
            abort(403);
        }
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No items selected!'], 400);
            }
            return \Illuminate\Support\Facades\Redirect::back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please select an action!'], 400);
            }
            return \Illuminate\Support\Facades\Redirect::back()->with('error', __('Please select an action!'));
        }
        $allServices = get_bookable_services();
        if ($action == "delete") {
            foreach ($ids as $id) {
                $review = Review::withTrashed()->where('id', $id)->first();
                if(!empty($review)){
                    // Also delete related meta
                    ReviewMeta::where('review_id', $review->id)->delete();
                    // Use forceDelete to permanently delete (bypass soft delete)
                    $review->forceDelete();
                    $module_class = $allServices[$review->object_model] ?? false;
                    if(!empty($module_class)){
                        $model_serivce = $module_class::withTrashed()->find($review->object_id);
                        if(!empty($model_serivce)){
                            Cache::forget('review_' . ($model_serivce->type ?? $review->object_model) . '_' . $review->object_id);
                            // Only call update_service_rate if method exists
                            if (method_exists($model_serivce, 'update_service_rate')) {
                                $model_serivce->update_service_rate();
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($ids as $id) {
                $review = Review::where('id', $id)->first();
                if (!empty($review)) {
                    $review->status = $action;
                    $review->save();
                    $module_class = $allServices[$review->object_model] ?? false;
                    if(!empty($module_class)){
                        $model_serivce = $module_class::withTrashed()->find($review->object_id);
                        if(!empty($model_serivce)){
                            Cache::forget('review_' . ($model_serivce->type ?? $review->object_model) . '_' . $review->object_id);
                            // Only call update_service_rate if method exists
                            if (method_exists($model_serivce, 'update_service_rate')) {
                                $model_serivce->update_service_rate();
                            }
                        }
                    }
                }
            }
        }
        
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Update success!']);
        }

        return \Illuminate\Support\Facades\Redirect::back()->with('success', __('Update success!'));
    }
}
