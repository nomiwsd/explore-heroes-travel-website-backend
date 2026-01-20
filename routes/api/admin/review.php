<?php

/**
 * ADMIN REVIEW MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Review\Models\Review;

// =====================================================
// REVIEW MANAGEMENT
// =====================================================
Route::prefix('module/review')->middleware('auth:sanctum')->group(function () {
    // Get all reviews
    Route::get('/', function (Request $request) {
        try {
            $query = Review::with(['author']);
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'LIKE', '%' . $request->s . '%')
                      ->orWhere('content', 'LIKE', '%' . $request->s . '%');
                });
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Rating filter
            if ($request->has('rating') && $request->rating) {
                $query->where('rate_number', $request->rating);
            }
            
            // Object type filter
            if ($request->has('object_model') && $request->object_model) {
                $query->where('object_model', $request->object_model);
            }
            
            $reviews = $query->orderBy('id', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $reviews->items(),
                'total' => $reviews->total(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single review
    Route::get('/edit/{id}', function ($id) {
        try {
            $review = Review::with(['author'])->findOrFail($id);

            // Get meta
            $meta = \Illuminate\Support\Facades\DB::table('bc_review_meta')->where('review_id', $review->id)->pluck('val', 'name')->toArray();
            
            return response()->json([
                'data' => [
                    'id' => $review->id,
                    'title' => $review->title,
                    'content' => $review->content,
                    'rating' => $review->rate_number, 
                    'rate_number' => $review->rate_number,
                    'object_id' => $review->object_id,
                    'object_model' => $review->object_model,
                    'status' => $review->status,
                    'is_featured' => $review->is_featured,
                    'show_on_homepage' => $review->show_on_homepage,
                    'show_on_tour_page' => $review->show_on_tour_page,
                    'author' => $review->author,
                    'author_name' => $review->author_name ?? ($meta['author_name'] ?? ($review->author ? $review->author->name : 'Anonymous')),
                    'author_email' => $review->author_email ?? ($meta['author_email'] ?? ($review->author ? $review->author->email : null)),
                    'review_source' => $review->review_source ?? ($meta['review_source'] ?? 'website'),
                    'review_date' => $review->review_date ?? ($meta['review_date'] ?? $review->created_at),
                    'author_avatar' => $review->author_avatar ?? ($meta['author_avatar'] ?? null),
                    'author_location' => $review->author_location ?? ($meta['author_location'] ?? null),
                    'author_country' => $review->author_country ?? ($meta['author_country'] ?? null),
                    'service' => $review->service,
                    'created_at' => $review->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Create review
    Route::post('/store', function (Request $request) {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'title' => 'required',
                'content' => 'required',
                'rating' => 'required|numeric',
                'object_id' => 'required_without:tour_id',
                'tour_id' => 'required_without:object_id',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            // Map tour_id to object_id if present
            $objectId = $request->input('object_id');
            $objectModel = $request->input('object_model', 'tour');

            if ($request->has('tour_id') && $request->input('tour_id')) {
                $objectId = $request->input('tour_id');
                $objectModel = 'tour';
            }

            $review = new Review([
                'object_id' => $objectId,
                'object_model' => $objectModel,
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'rate_number' => $request->input('rating'),
                'status' => $request->input('status', 'pending'),
                'is_featured' => $request->input('is_featured', 0),
                'show_on_homepage' => $request->input('show_on_homepage', 0),
                'show_on_tour_page' => $request->input('show_on_tour_page', 1),
                'author_id' => auth()->id() ?? 1,

                // Extended Columns
                'author_name' => $request->input('author_name'),
                'author_email' => $request->input('author_email'),
                'review_source' => $request->input('review_source'),
                'review_date' => $request->input('review_date'),
                'author_avatar' => $request->input('author_avatar'),
                'author_location' => $request->input('author_location'),
                'author_country' => $request->input('author_country'),
            ]);

            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review created successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update review
    Route::post('/store/{id}', function (Request $request, $id) {
        try {
            $review = Review::findOrFail($id);
            
            $review->title = $request->input('title', $review->title);
            $review->content = $request->input('content', $review->content);
            $review->rate_number = $request->input('rating', $request->input('rate_number', $review->rate_number));
            $review->status = $request->input('status', $review->status);
            $review->is_featured = $request->input('is_featured', $review->is_featured);
            $review->show_on_homepage = $request->input('show_on_homepage', $review->show_on_homepage);
            $review->show_on_tour_page = $request->input('show_on_tour_page', $review->show_on_tour_page);

            $review->author_name = $request->input('author_name', $review->author_name);
            $review->author_email = $request->input('author_email', $review->author_email);
            $review->review_source = $request->input('review_source', $review->review_source);
            $review->review_date = $request->input('review_date', $review->review_date);
            $review->author_avatar = $request->input('author_avatar', $review->author_avatar);
            $review->author_location = $request->input('author_location', $review->author_location);
            $review->author_country = $request->input('author_country', $review->author_country);
            
            $review->save();

            return response()->json([
                'success' => true,
                'data' => ['id' => $review->id],
                'message' => 'Review updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Approve review
    Route::post('/approve/{id}', function ($id) {
        try {
            $review = Review::findOrFail($id);
            $review->status = 'approved';
            $review->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Review approved',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Reject review
    Route::post('/reject/{id}', function ($id) {
        try {
            $review = Review::findOrFail($id);
            $review->status = 'rejected';
            $review->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Review rejected',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete review
    Route::delete('/{id}', function ($id) {
        try {
            $review = Review::findOrFail($id);
            $review->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    Review::whereIn('id', $ids)->delete();
                    break;
                case 'approve':
                    Review::whereIn('id', $ids)->update(['status' => 'approved']);
                    break;
                case 'pending':
                    Review::whereIn('id', $ids)->update(['status' => 'pending']);
                    break;
                case 'reject':
                    Review::whereIn('id', $ids)->update(['status' => 'rejected']);
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
    
    // Get statistics
    Route::get('/statistics', function () {
        try {
            $stats = [
                'total' => Review::count(),
                'approved' => Review::where('status', 'approved')->count(),
                'pending' => Review::where('status', 'pending')->count(),
                'rejected' => Review::where('status', 'rejected')->count(),
                'average_rating' => round(Review::where('status', 'approved')->avg('rate_number'), 1),
                'by_rating' => [
                    '5' => Review::where('rate_number', 5)->where('status', 'approved')->count(),
                    '4' => Review::where('rate_number', 4)->where('status', 'approved')->count(),
                    '3' => Review::where('rate_number', 3)->where('status', 'approved')->count(),
                    '2' => Review::where('rate_number', 2)->where('status', 'approved')->count(),
                    '1' => Review::where('rate_number', 1)->where('status', 'approved')->count(),
                ],
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
});
