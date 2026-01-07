<?php

/**
 * ADMIN REPORTS MODULE ROUTES
 * As requested - keeping reports module
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Tour\Models\Tour;
use Modules\Location\Models\Location;
use Modules\News\Models\News;
use Modules\Review\Models\Review;
use Modules\Contact\Models\Contact;
use App\User;

// =====================================================
// REPORTS & ANALYTICS
// =====================================================
Route::prefix('module/report')->middleware('auth:sanctum')->group(function () {
    // Overview Report
    Route::get('/overview', function (Request $request) {
        try {
            $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            
            $report = [
                'tours' => [
                    'total' => Tour::count(),
                    'published' => Tour::where('status', 'publish')->count(),
                    'featured' => Tour::where('is_featured', true)->count(),
                    'recent' => Tour::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                ],
                'destinations' => [
                    'total' => Location::count(),
                    'published' => Location::where('status', 'publish')->count(),
                    'featured' => Location::where('is_featured', true)->count(),
                ],
                'news' => [
                    'total' => News::count(),
                    'published' => News::where('status', 'publish')->count(),
                    'recent' => News::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                ],
                'reviews' => [
                    'total' => Review::count(),
                    'approved' => Review::where('status', 'approved')->count(),
                    'pending' => Review::where('status', 'pending')->count(),
                    'average_rating' => round(Review::where('status', 'approved')->avg('rate_number'), 1),
                ],
                'contacts' => [
                    'total' => Contact::count(),
                    'new' => Contact::where('status', 'new')->count(),
                    'recent' => Contact::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                ],
                'users' => [
                    'total' => User::count(),
                    'new' => User::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                ],
            ];
            
            return response()->json($report);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Tour Statistics
    Route::get('/tours', function (Request $request) {
        try {
            $dateFrom = $request->input('date_from', now()->subMonths(6)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            
            // Tours by month
            $toursByMonth = Tour::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            // Tours by category
            $toursByCategory = \DB::table('bc_tour_category')
                ->leftJoin('bc_tours', 'bc_tour_category.id', '=', 'bc_tours.category_id')
                ->select('bc_tour_category.name', \DB::raw('COUNT(bc_tours.id) as count'))
                ->groupBy('bc_tour_category.id', 'bc_tour_category.name')
                ->get();
            
            // Tours by location
            $toursByLocation = Location::withCount('tours')
                ->orderByDesc('tours_count')
                ->limit(10)
                ->get(['id', 'name', 'tours_count']);
            
            // Most viewed tours (if views column exists)
            $popularTours = Tour::where('status', 'publish')
                ->orderByDesc('views')
                ->limit(10)
                ->get(['id', 'title', 'views', 'price']);
            
            return response()->json([
                'by_month' => $toursByMonth,
                'by_category' => $toursByCategory,
                'by_location' => $toursByLocation,
                'popular' => $popularTours,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Review Statistics
    Route::get('/reviews', function (Request $request) {
        try {
            $dateFrom = $request->input('date_from', now()->subMonths(6)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            
            // Reviews by month
            $reviewsByMonth = Review::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, AVG(rate_number) as avg_rating')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            // Rating distribution
            $ratingDistribution = Review::where('status', 'approved')
                ->selectRaw('rate_number as rating, COUNT(*) as count')
                ->groupBy('rate_number')
                ->orderBy('rate_number', 'desc')
                ->get();
            
            // Top reviewed tours
            $topReviewed = Tour::withCount(['reviews' => function ($q) {
                    $q->where('status', 'approved');
                }])
                ->having('reviews_count', '>', 0)
                ->orderByDesc('reviews_count')
                ->limit(10)
                ->get(['id', 'title', 'reviews_count']);
            
            return response()->json([
                'by_month' => $reviewsByMonth,
                'rating_distribution' => $ratingDistribution,
                'top_reviewed' => $topReviewed,
                'overall_average' => round(Review::where('status', 'approved')->avg('rate_number'), 1),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Contact Statistics
    Route::get('/contacts', function (Request $request) {
        try {
            $dateFrom = $request->input('date_from', now()->subMonths(6)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            
            // Contacts by month
            $contactsByMonth = Contact::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            // Contacts by status
            $contactsByStatus = Contact::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
            
            // Response rate
            $totalContacts = Contact::count();
            $repliedContacts = Contact::where('status', 'replied')->count();
            $responseRate = $totalContacts > 0 ? round(($repliedContacts / $totalContacts) * 100, 1) : 0;
            
            return response()->json([
                'by_month' => $contactsByMonth,
                'by_status' => $contactsByStatus,
                'response_rate' => $responseRate,
                'total' => $totalContacts,
                'replied' => $repliedContacts,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // User Statistics
    Route::get('/users', function (Request $request) {
        try {
            $dateFrom = $request->input('date_from', now()->subMonths(6)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            
            // Users by month
            $usersByMonth = User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            // Users by role
            $usersByRole = [
                ['role' => 'Super Admin', 'count' => User::where('role_id', 1)->count()],
                ['role' => 'Admin', 'count' => User::where('role_id', 2)->count()],
                ['role' => 'User', 'count' => User::where('role_id', 3)->count()],
            ];
            
            // Users by status
            $usersByStatus = [
                ['status' => 'Active', 'count' => User::where('status', 'publish')->count()],
                ['status' => 'Blocked', 'count' => User::where('status', 'blocked')->count()],
            ];
            
            return response()->json([
                'by_month' => $usersByMonth,
                'by_role' => $usersByRole,
                'by_status' => $usersByStatus,
                'total' => User::count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Content Summary
    Route::get('/content', function () {
        try {
            $summary = [
                ['type' => 'Tours', 'total' => Tour::count(), 'published' => Tour::where('status', 'publish')->count()],
                ['type' => 'Destinations', 'total' => Location::count(), 'published' => Location::where('status', 'publish')->count()],
                ['type' => 'Blog Posts', 'total' => News::count(), 'published' => News::where('status', 'publish')->count()],
                ['type' => 'Pages', 'total' => \DB::table('bc_pages')->count(), 'published' => \DB::table('bc_pages')->where('status', 'publish')->count()],
            ];
            
            return response()->json(['content_summary' => $summary]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Export Report
    Route::get('/export/{type}', function ($type, Request $request) {
        try {
            $dateFrom = $request->input('date_from', now()->subMonths(6)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            $data = [];
            
            switch ($type) {
                case 'tours':
                    $data = Tour::whereBetween('created_at', [$dateFrom, $dateTo])
                        ->get(['id', 'title', 'price', 'status', 'views', 'created_at']);
                    break;
                case 'reviews':
                    $data = Review::with('author:id,name')
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->get(['id', 'title', 'rate_number', 'status', 'author_id', 'created_at']);
                    break;
                case 'contacts':
                    $data = Contact::whereBetween('created_at', [$dateFrom, $dateTo])
                        ->get(['id', 'name', 'email', 'subject', 'status', 'created_at']);
                    break;
                case 'users':
                    $data = User::whereBetween('created_at', [$dateFrom, $dateTo])
                        ->get(['id', 'name', 'email', 'role_id', 'status', 'created_at']);
                    break;
                default:
                    return response()->json(['error' => 'Invalid report type'], 400);
            }
            
            return response()->json([
                'type' => $type,
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'data' => $data,
                'total' => count($data),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
