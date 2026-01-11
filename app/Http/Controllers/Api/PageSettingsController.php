<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageSettingsController extends Controller
{
    /**
     * Get page settings by slug (for frontend rendering)
     */
    public function show(Request $request, string $slug)
    {
        $useDraft = $request->has('preview') && $request->get('preview_token');
        
        $setting = PageSetting::where('page_slug', $slug)->first();
        
        if (!$setting) {
            // Return defaults if no settings exist
            return response()->json([
                'page_slug' => $slug,
                'page_title' => ucfirst(str_replace('-', ' ', $slug)),
                'sections' => PageSetting::getDefaultSections($slug),
                'is_default' => true,
            ]);
        }

        $sections = $useDraft && $setting->draft_sections 
            ? $setting->draft_sections 
            : $setting->sections;

        return response()->json([
            'page_slug' => $setting->page_slug,
            'page_title' => $setting->page_title,
            'sections' => $sections ?? PageSetting::getDefaultSections($slug),
            'is_published' => $setting->is_published,
            'has_draft' => !empty($setting->draft_sections),
        ]);
    }

    /**
     * Get all page settings (admin list)
     */
    public function index()
    {
        $pages = ['home', 'about', 'faq', 'contact', 'success-stories'];
        $result = [];

        foreach ($pages as $slug) {
            $setting = PageSetting::where('page_slug', $slug)->first();
            $result[] = [
                'page_slug' => $slug,
                'page_title' => $setting->page_title ?? ucfirst(str_replace('-', ' ', $slug)),
                'is_configured' => $setting !== null,
                'has_draft' => $setting && !empty($setting->draft_sections),
                'updated_at' => $setting?->updated_at,
            ];
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Update page settings (admin)
     */
    public function update(Request $request, string $slug)
    {
        $request->validate([
            'page_title' => 'nullable|string|max:255',
            'sections' => 'required|array',
        ]);

        $setting = PageSetting::firstOrCreate(
            ['page_slug' => $slug],
            ['page_title' => ucfirst(str_replace('-', ' ', $slug))]
        );

        // Save to draft for preview
        if ($request->get('save_as_draft', false)) {
            $setting->draft_sections = $request->input('sections');
        } else {
            $setting->sections = $request->input('sections');
            $setting->draft_sections = null;
        }

        if ($request->has('page_title')) {
            $setting->page_title = $request->input('page_title');
        }

        $setting->save();

        return response()->json([
            'message' => $request->get('save_as_draft') ? 'Draft saved' : 'Settings updated',
            'data' => $setting,
        ]);
    }

    /**
     * Publish draft to live
     */
    public function publish(string $slug)
    {
        $setting = PageSetting::where('page_slug', $slug)->first();
        
        if (!$setting || empty($setting->draft_sections)) {
            return response()->json(['error' => 'No draft to publish'], 400);
        }

        $setting->publish();

        return response()->json([
            'message' => 'Published successfully',
            'data' => $setting,
        ]);
    }

    /**
     * Get preview token for a page
     */
    public function getPreviewToken(string $slug)
    {
        $setting = PageSetting::where('page_slug', $slug)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        // Generate a simple preview token (in production, use more secure method)
        $token = Str::random(32);
        
        // Store token temporarily (could use cache instead)
        cache()->put("preview_token_{$slug}", $token, now()->addHours(1));

        return response()->json([
            'preview_url' => config('app.frontend_url', 'http://localhost:3000') . "/preview/{$slug}?token={$token}",
            'token' => $token,
            'expires_at' => now()->addHours(1)->toISOString(),
        ]);
    }

    /**
     * Validate preview token
     */
    public function validatePreviewToken(Request $request, string $slug)
    {
        $token = $request->get('token');
        $storedToken = cache()->get("preview_token_{$slug}");

        if (!$token || $token !== $storedToken) {
            return response()->json(['valid' => false], 401);
        }

        return response()->json(['valid' => true]);
    }
}
