<?php

namespace Modules\Core\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Settings;

class SeoController extends Controller
{
    // Get global SEO settings
    public function getGlobalSeo()
    {
        $seoSettings = [
            'site_title' => setting_item('seo_site_title', config('app.name')),
            'meta_description' => setting_item('seo_meta_description'),
            'meta_keywords' => setting_item('seo_meta_keywords'),
            'og_image' => setting_item('seo_og_image'),
            'favicon' => setting_item('site_favicon'),
            'robots_txt' => $this->getRobotsTxtContent(),
            'sitemap_enabled' => setting_item('sitemap_enabled', 1),
            'sitemap_frequency' => setting_item('sitemap_frequency', 'weekly'),
            'sitemap_priority' => setting_item('sitemap_priority', 0.8),
        ];

        return response()->json($seoSettings);
    }

    // Update global SEO settings
    public function updateGlobalSeo(Request $request)
    {
        $settings = $request->all();
        
        foreach ($settings as $key => $value) {
            if ($key === 'robots_txt') continue; // Handle separately
            
            Settings::where('name', 'seo_' . $key)->updateOrCreate(
                ['name' => 'seo_' . $key],
                ['val' => $value]
            );
        }

        return response()->json(['success' => true, 'message' => 'SEO settings updated']);
    }

    // Get all redirects
    public function getRedirects(Request $request)
    {
        $query = DB::table('core_redirects');
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('old_url', 'like', "%{$search}%")
                  ->orWhere('new_url', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $redirects = $query->orderBy('created_at', 'desc')->get();

        return response()->json($redirects);
    }

    // Get single redirect
    public function getRedirect($id)
    {
        $redirect = DB::table('core_redirects')->find($id);
        return response()->json($redirect);
    }

    // Create redirect
    public function createRedirect(Request $request)
    {
        $request->validate([
            'old_url' => 'required|string',
            'new_url' => 'required|string',
            'status_code' => 'required|integer|in:301,302',
        ]);

        $id = DB::table('core_redirects')->insertGetId([
            'old_url' => $request->old_url,
            'new_url' => $request->new_url,
            'status_code' => $request->status_code,
            'is_active' => $request->is_active ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $redirect = DB::table('core_redirects')->find($id);
        return response()->json($redirect);
    }

    // Update redirect
    public function updateRedirect($id, Request $request)
    {
        $request->validate([
            'old_url' => 'required|string',
            'new_url' => 'required|string',
            'status_code' => 'required|integer|in:301,302',
        ]);

        DB::table('core_redirects')->where('id', $id)->update([
            'old_url' => $request->old_url,
            'new_url' => $request->new_url,
            'status_code' => $request->status_code,
            'is_active' => $request->is_active ?? 1,
            'updated_at' => now(),
        ]);

        $redirect = DB::table('core_redirects')->find($id);
        return response()->json($redirect);
    }

    // Bulk edit redirects
    public function bulkEditRedirects(Request $request)
    {
        $ids = $request->ids;
        $action = $request->action;

        if ($action === 'delete') {
            DB::table('core_redirects')->whereIn('id', $ids)->delete();
        } elseif ($action === 'activate') {
            DB::table('core_redirects')->whereIn('id', $ids)->update(['is_active' => 1]);
        } elseif ($action === 'deactivate') {
            DB::table('core_redirects')->whereIn('id', $ids)->update(['is_active' => 0]);
        }

        return response()->json(['success' => true]);
    }

    // Import redirects from CSV
    public function importRedirects(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file));
        
        $success = 0;
        $failed = 0;

        foreach ($csvData as $index => $row) {
            if ($index === 0) continue; // Skip header
            
            if (count($row) >= 2) {
                try {
                    DB::table('core_redirects')->insert([
                        'old_url' => $row[0],
                        'new_url' => $row[1],
                        'status_code' => $row[2] ?? 301,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                }
            }
        }

        return response()->json(['success' => $success, 'failed' => $failed]);
    }

    // Get sitemap settings
    public function getSitemapSettings()
    {
        return response()->json([
            'enabled' => (bool) setting_item('sitemap_enabled', 1),
            'include_pages' => (bool) setting_item('sitemap_include_pages', 1),
            'include_tours' => (bool) setting_item('sitemap_include_tours', 1),
            'include_destinations' => (bool) setting_item('sitemap_include_destinations', 1),
            'include_blog' => (bool) setting_item('sitemap_include_blog', 1),
            'frequency' => setting_item('sitemap_frequency', 'weekly'),
            'priority' => (float) setting_item('sitemap_priority', 0.8),
            'exclude_urls' => json_decode(setting_item('sitemap_exclude_urls', '[]'), true),
        ]);
    }

    // Update sitemap settings
    public function updateSitemapSettings(Request $request)
    {
        $settings = $request->all();
        
        foreach ($settings as $key => $value) {
            if ($key === 'exclude_urls') {
                $value = json_encode($value);
            }
            
            Settings::where('name', 'sitemap_' . $key)->updateOrCreate(
                ['name' => 'sitemap_' . $key],
                ['val' => $value]
            );
        }

        return response()->json(['success' => true]);
    }

    // Get robots.txt content
    public function getRobotsTxt()
    {
        return response()->json([
            'content' => $this->getRobotsTxtContent()
        ]);
    }

    // Update robots.txt
    public function updateRobotsTxt(Request $request)
    {
        $content = $request->content;
        
        Settings::where('name', 'robots_txt')->updateOrCreate(
            ['name' => 'robots_txt'],
            ['val' => $content]
        );

        return response()->json(['success' => true]);
    }

    // Helper to get robots.txt content
    private function getRobotsTxtContent()
    {
        return setting_item('robots_txt', "User-agent: *\nAllow: /\nSitemap: " . url('/sitemap.xml'));
    }
}
