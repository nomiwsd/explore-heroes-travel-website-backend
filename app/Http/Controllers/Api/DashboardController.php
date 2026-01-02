<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Contact\Models\Contact;
use Modules\Tour\Models\Tour;
use Modules\Location\Models\Location;
use Modules\Page\Models\Page;
use Modules\Review\Models\Review;
use Modules\Core\Models\ActivityLog;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request)
    {
        try {
            // Time filters
            $today = Carbon::today();
            $last24Hours = Carbon::now()->subHours(24);
            $last7Days = Carbon::now()->subDays(7);

            // Contact/Inquiry Statistics
            $totalInquiries = Contact::count();
            $newInquiriesLast24h = Contact::where('created_at', '>=', $last24Hours)->count();
            $newInquiriesLast7Days = Contact::where('created_at', '>=', $last7Days)->count();

            // Unread submissions (status = 'new' or null)
            $unreadSubmissions = Contact::where(function($query) {
                $query->where('status', 'new')
                      ->orWhereNull('status');
            })->count();

            // Quote requests (form_type = 'quote')
            $quoteRequests = Contact::where('form_type', 'quote')->count();

            // Replied submissions
            $repliedSubmissions = Contact::where('status', 'replied')->count();

            // Tours Statistics
            $toursLive = Tour::where('status', 'publish')->count();
            $draftTours = Tour::where('status', 'draft')->count();
            $totalTours = Tour::count();

            // Destinations Statistics
            $destinationsLive = Location::where('status', 'publish')->count();
            $totalDestinations = Location::count();

            // Pages Statistics
            $draftPages = Page::where('status', 'draft')->count();
            $totalPages = Page::count();

            // Missing SEO - pages and tours without meta_title or meta_desc
            $missingSeoTours = DB::table('bc_tours')
                ->leftJoin('core_seos', function($join) {
                    $join->on('bc_tours.id', '=', 'core_seos.object_id')
                         ->where('core_seos.object_model', '=', 'tour');
                })
                ->where('bc_tours.deleted_at', null)
                ->where(function($query) {
                    $query->whereNull('core_seos.seo_title')
                          ->orWhere('core_seos.seo_title', '')
                          ->orWhereNull('core_seos.seo_desc')
                          ->orWhere('core_seos.seo_desc', '');
                })
                ->count();

            $missingSeoPages = DB::table('core_pages')
                ->leftJoin('core_seos', function($join) {
                    $join->on('core_pages.id', '=', 'core_seos.object_id')
                         ->where('core_seos.object_model', '=', 'page');
                })
                ->where('core_pages.deleted_at', null)
                ->where(function($query) {
                    $query->whereNull('core_seos.seo_title')
                          ->orWhere('core_seos.seo_title', '')
                          ->orWhereNull('core_seos.seo_desc')
                          ->orWhere('core_seos.seo_desc', '');
                })
                ->count();

            $missingSeo = $missingSeoTours + $missingSeoPages;

            // Pending Reviews
            $pendingReviews = 0;
            if (class_exists('Modules\Review\Models\Review')) {
                $pendingReviews = Review::where('status', 'pending')->count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    // KPI Stats
                    'kpi' => [
                        'new_inquiries' => [
                            'count' => $newInquiriesLast24h,
                            'last_7_days' => $newInquiriesLast7Days,
                            'total' => $totalInquiries,
                        ],
                        'unread_submissions' => $unreadSubmissions,
                        'quote_requests' => $quoteRequests,
                        'replied_submissions' => $repliedSubmissions,
                        'tours_live' => $toursLive,
                        'destinations_live' => $destinationsLive,
                    ],
                    // Content Status
                    'content_status' => [
                        'draft_tours' => $draftTours,
                        'draft_pages' => $draftPages,
                        'missing_seo' => $missingSeo,
                        'pending_reviews' => $pendingReviews,
                    ],
                    // Summary Stats
                    'summary' => [
                        'total_tours' => $totalTours,
                        'total_destinations' => $totalDestinations,
                        'total_pages' => $totalPages,
                        'total_inquiries' => $totalInquiries,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get latest form submissions for dashboard
     */
    public function getLatestSubmissions(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $submissions = Contact::with('tour:id,title')
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'message',
                'status',
                'form_type',
                'tour_id',
                'created_at',
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->id,
                    'name' => $submission->name,
                    'email' => $submission->email,
                    'phone' => $submission->phone,
                    'message' => $submission->message,
                    'status' => $submission->status ?? 'new',
                    'form_type' => $submission->form_type ?? 'contact',
                    'tour_name' => $submission->tour ? $submission->tour->title : null,
                    'created_at' => $submission->created_at,
                    'created_at_formatted' => $submission->created_at
                        ? Carbon::parse($submission->created_at)->diffForHumans()
                        : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $submissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch latest submissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity log for dashboard
     */
    public function getActivityLog(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            // Check if activity log table exists
            if (!DB::getSchemaBuilder()->hasTable('core_activity_logs')) {
                // Return simulated activity from recent changes
                $activities = $this->getSimulatedActivityLog($limit);
                return response()->json([
                    'success' => true,
                    'data' => $activities,
                ]);
            }

            $activities = DB::table('core_activity_logs')
                ->select(['id', 'action', 'object_type', 'object_name', 'user_name', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($activity) {
                    return [
                        'id' => $activity->id,
                        'action' => $activity->action,
                        'object_type' => $activity->object_type,
                        'object_name' => $activity->object_name,
                        'user_name' => $activity->user_name,
                        'created_at' => $activity->created_at,
                        'created_at_formatted' => Carbon::parse($activity->created_at)->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity log',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get simulated activity log from recent changes
     */
    private function getSimulatedActivityLog($limit = 10)
    {
        $activities = collect();

        // Get recent tour updates
        $recentTours = Tour::orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'updated_at', 'created_at']);

        foreach ($recentTours as $tour) {
            $isNew = $tour->created_at->eq($tour->updated_at);
            $activities->push([
                'id' => 'tour_' . $tour->id,
                'action' => $isNew ? 'created' : 'updated',
                'object_type' => 'Tour',
                'object_name' => $tour->title,
                'user_name' => 'Admin',
                'created_at' => $tour->updated_at,
                'created_at_formatted' => Carbon::parse($tour->updated_at)->diffForHumans(),
            ]);
        }

        // Get recent page updates
        $recentPages = Page::orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'updated_at', 'created_at']);

        foreach ($recentPages as $page) {
            $isNew = $page->created_at && $page->updated_at &&
                     Carbon::parse($page->created_at)->eq(Carbon::parse($page->updated_at));
            $activities->push([
                'id' => 'page_' . $page->id,
                'action' => $isNew ? 'created' : 'updated',
                'object_type' => 'Page',
                'object_name' => $page->title,
                'user_name' => 'Admin',
                'created_at' => $page->updated_at,
                'created_at_formatted' => Carbon::parse($page->updated_at)->diffForHumans(),
            ]);
        }

        // Get recent location updates
        $recentLocations = Location::orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'updated_at', 'created_at']);

        foreach ($recentLocations as $location) {
            $isNew = $location->created_at && $location->updated_at &&
                     Carbon::parse($location->created_at)->eq(Carbon::parse($location->updated_at));
            $activities->push([
                'id' => 'location_' . $location->id,
                'action' => $isNew ? 'created' : 'updated',
                'object_type' => 'Destination',
                'object_name' => $location->name,
                'user_name' => 'Admin',
                'created_at' => $location->updated_at,
                'created_at_formatted' => Carbon::parse($location->updated_at)->diffForHumans(),
            ]);
        }

        // Sort by created_at and limit
        return $activities->sortByDesc('created_at')->take($limit)->values();
    }

    /**
     * Get website status
     */
    public function getWebsiteStatus(Request $request)
    {
        try {
            // Check various indicators of website health
            $status = 'live'; // Default to live

            // You can add custom logic here to determine website status
            // For example, checking if there's a maintenance flag in settings

            $lastPublishDate = null;

            // Get the most recent published content date
            $lastTourPublish = Tour::where('status', 'publish')
                ->orderBy('updated_at', 'desc')
                ->first();

            $lastPagePublish = Page::where('status', 'publish')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastTourPublish && $lastPagePublish) {
                $lastPublishDate = max($lastTourPublish->updated_at, $lastPagePublish->updated_at);
            } elseif ($lastTourPublish) {
                $lastPublishDate = $lastTourPublish->updated_at;
            } elseif ($lastPagePublish) {
                $lastPublishDate = $lastPagePublish->updated_at;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'last_publish_date' => $lastPublishDate,
                    'last_publish_formatted' => $lastPublishDate
                        ? Carbon::parse($lastPublishDate)->diffForHumans()
                        : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch website status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update submission status quickly from dashboard
     */
    public function updateSubmissionStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:new,read,replied,archived',
            ]);

            $submission = Contact::findOrFail($id);
            $submission->status = $request->input('status');
            $submission->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $submission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update submission status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
