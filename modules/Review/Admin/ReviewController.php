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
            $model->whereRaw(" ( title LIKE ? OR author_ip LIKE ? OR content LIKE ? ) ",[$search_name,$search_name,$search_name]);
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
        if (!empty($object_model = $request->input('object_model')) and in_array($object_model,$allServicesKeys)) {
            $model->where('object_model', $object_model );
        }
        $model->whereIn('object_model', $allServicesKeys );


        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            $reviews = $model->with(['author'])->paginate(20);

            // Manually construct response to avoid IDE errors with toArray()
            $response = [
                'current_page' => $reviews->currentPage(),
                'data' => $reviews->items(),
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

            // Transform data manually
            $response['data'] = collect($reviews->items())->map(function ($review) {
                // Get Tour Name and Slug
                $tourName = null;
                $tourData = null;
                if ($review->object_model === 'tour' && $review->object_id) {
                    $tour = \Modules\Tour\Models\Tour::find($review->object_id);
                    if ($tour) {
                        $tourName = $tour->title;
                        $tourData = [
                            'id' => $tour->id,
                            'title' => $tour->title,
                            'slug' => $tour->slug,
                        ];
                    }
                }

                // Get Review Meta
                $meta = ReviewMeta::where('review_id', $review->id)->get();
                $metaData = [];
                $imageIds = [];
                foreach ($meta as $m) {
                    if ($m->name === 'review_image') {
                        $imageIds[] = (int) $m->val;
                    } else {
                        // Handle boolean fields
                        if (in_array($m->name, ['show_on_homepage', 'show_on_tour_page', 'is_featured'])) {
                            $metaData[$m->name] = ($m->val === '1' || $m->val === 'true');
                        } else {
                            $metaData[$m->name] = $m->val;
                        }
                    }
                }

                // Get Images
                $imagesWithUrls = [];
                if (!empty($imageIds)) {
                    $mediaFiles = MediaFile::whereIn('id', $imageIds)->get();
                    foreach ($imageIds as $imgId) {
                        $media = $mediaFiles->firstWhere('id', $imgId);
                        if ($media) {
                            $filePath = $media->file_path;
                            if (!str_starts_with($filePath, 'uploads/')) {
                                $filePath = 'uploads/' . $filePath;
                            }
                            $imagesWithUrls[] = [
                                'id' => $media->id,
                                'url' => $filePath,
                                'file_path' => $media->file_path,
                            ];
                        }
                    }
                }

                // Get Author Avatar from User or Meta
                $authorAvatar = null;
                if ($review->author && $review->author->avatar_id) {
                    $authorAvatar = get_file_url($review->author->avatar_id, 'full');
                } elseif (isset($metaData['author_avatar'])) {
                    $authorAvatar = $metaData['author_avatar'];
                }

                // Convert model to array
                $data = $review->toArray();

                // Add/Override fields
                $data['rating'] = $review->rate_number ?? 0;
                $data['tour_name'] = $tourName; // Keep for simple table display
                $data['tour_id'] = ($review->object_model === 'tour') ? $review->object_id : null;
                $data['tour'] = $tourData;
                $data['author_name'] = $review->author ? $review->author->name : ($metaData['author_name'] ?? ($review->author_name ?? 'Anonymous'));
                $data['author_email'] = $review->author ? $review->author->email : ($metaData['author_email'] ?? ($review->email ?? ''));
                $data['author_avatar'] = $authorAvatar;
                $data['images'] = $imagesWithUrls;

                // Merge all meta fields
                $data = array_merge($data, $metaData);

                // Ensure defaults
                $defaults = [
                    'author_location' => '',
                    'author_country' => '',
                    'review_source' => 'website',
                    'review_date' => '',
                    'show_on_homepage' => false,
                    'is_featured' => false,
                    'trip_summary' => '',
                    'agent_name' => '',
                    'agent_photo' => '',
                ];

                return array_merge($defaults, $data);
            });

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
        
        // Get review meta
        $meta = ReviewMeta::where('review_id', $id)->get();
        
        // Return JSON for API requests - transform to flat format matching create payload
        if ($request->wantsJson() || $request->expectsJson()) {
            // Build flat response matching create payload format
            $responseData = [
                'id' => $review->id,
                'title' => $review->title,
                'content' => $review->content,
                'rating' => $review->rate_number,
                'status' => $review->status,
                'object_id' => $review->object_id,
                'object_model' => $review->object_model,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
                'author' => $review->author,
                // Defaults
                'author_name' => $review->author ? $review->author->name : '',
                'author_email' => $review->author ? $review->author->email : '',
                'author_avatar' => '',
                'author_location' => '',
                'author_country' => '',
                'review_source' => 'website',
                'review_date' => '',
                'show_on_homepage' => false,
                'show_on_tour_page' => true,
                'is_featured' => false,
                'trip_summary' => '',
                'agent_name' => '',
                'agent_role' => '',
                'agent_photo' => '',
                'tour_id' => $review->object_model === 'tour' ? $review->object_id : null,
                'images' => [],
                'tour' => null,
            ];
            
            // Parse meta into flat properties
            $imageIds = [];
            foreach ($meta as $m) {
                switch ($m->name) {
                    case 'author_name':
                        $responseData['author_name'] = $m->val;
                        break;
                    case 'author_email':
                        $responseData['author_email'] = $m->val;
                        break;
                    case 'author_avatar':
                        $responseData['author_avatar'] = $m->val;
                        break;
                    case 'author_location':
                        $responseData['author_location'] = $m->val;
                        break;
                    case 'author_country':
                        $responseData['author_country'] = $m->val;
                        break;
                    case 'review_source':
                        $responseData['review_source'] = $m->val;
                        break;
                    case 'review_date':
                        $responseData['review_date'] = $m->val;
                        break;
                    case 'show_on_homepage':
                        $responseData['show_on_homepage'] = $m->val === '1' || $m->val === 'true';
                        break;
                    case 'show_on_tour_page':
                        $responseData['show_on_tour_page'] = $m->val === '1' || $m->val === 'true';
                        break;
                    case 'is_featured':
                        $responseData['is_featured'] = $m->val === '1' || $m->val === 'true';
                        break;
                    case 'trip_summary':
                        $responseData['trip_summary'] = $m->val;
                        break;
                    case 'agent_name':
                        $responseData['agent_name'] = $m->val;
                        break;
                    case 'agent_role':
                        $responseData['agent_role'] = $m->val;
                        break;
                    case 'agent_photo':
                        $responseData['agent_photo'] = $m->val;
                        break;
                    case 'review_image':
                        $imageIds[] = (int) $m->val;
                        break;
                }
            }
            
            // Fetch full image data with URLs
            if (!empty($imageIds)) {
                $mediaFiles = MediaFile::whereIn('id', $imageIds)->get();
                $imagesWithUrls = [];
                foreach ($imageIds as $imgId) {
                    $media = $mediaFiles->firstWhere('id', $imgId);
                    if ($media) {
                        // Use relative path without localhost
                        $filePath = $media->file_path;
                        if (!str_starts_with($filePath, 'uploads/')) {
                            $filePath = 'uploads/' . $filePath;
                        }
                        $imagesWithUrls[] = [
                            'id' => $media->id,
                            'url' => $filePath,
                            'file_path' => $media->file_path,
                        ];
                    }
                }
                $responseData['images'] = $imagesWithUrls;
            }
            
            // Fetch tour data if object_model is 'tour'
            if ($review->object_model === 'tour' && $review->object_id) {
                $tour = Tour::find($review->object_id);
                if ($tour) {
                    $responseData['tour'] = [
                        'id' => $tour->id,
                        'title' => $tour->title,
                        'slug' => $tour->slug,
                    ];
                }
            }
            
            return response()->json($responseData);
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
                $review = Review::where('id', $id)->first();
                if(!empty($review)){
                    $review->delete();
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
