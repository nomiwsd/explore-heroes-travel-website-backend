<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSetting extends Model
{
    protected $table = 'core_page_settings';
    
    protected $fillable = [
        'page_slug',
        'page_title',
        'sections',
        'draft_sections',
        'is_published',
    ];

    protected $casts = [
        'sections' => 'array',
        'draft_sections' => 'array',
        'is_published' => 'boolean',
    ];

    /**
     * Get section data by key
     */
    public function getSection(string $key, bool $useDraft = false): ?array
    {
        $data = $useDraft && $this->draft_sections ? $this->draft_sections : $this->sections;
        return $data[$key] ?? null;
    }

    /**
     * Check if a section is enabled
     */
    public function isSectionEnabled(string $key, bool $useDraft = false): bool
    {
        $section = $this->getSection($key, $useDraft);
        return $section['enabled'] ?? true;
    }

    /**
     * Get all sections in order
     */
    public function getOrderedSections(bool $useDraft = false): array
    {
        $data = $useDraft && $this->draft_sections ? $this->draft_sections : $this->sections;
        if (!$data) return [];

        // Sort by order if present
        uasort($data, function($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        return $data;
    }

    /**
     * Publish draft to live
     */
    public function publish(): void
    {
        $this->sections = $this->draft_sections;
        $this->draft_sections = null;
        $this->is_published = true;
        $this->save();
    }

    /**
     * Default sections structure for each page type
     */
    public static function getDefaultSections(string $pageSlug): array
    {
        $defaults = [
            'home' => [
                'hero' => ['enabled' => true, 'order' => 0, 'title' => 'Explore the World', 'subtitle' => 'Find your next adventure', 'bg_image' => null, 'cta_text' => 'Get Started', 'cta_link' => '/tours'],
                'how_it_works' => ['enabled' => true, 'order' => 1, 'title' => 'How It Works', 'steps' => []],
                'trending_destinations' => ['enabled' => true, 'order' => 2, 'title' => 'Trending Destinations', 'auto_fetch' => true, 'limit' => 6],
                'why_choose_us' => ['enabled' => true, 'order' => 3, 'title' => 'Why Choose Us', 'features' => []],
                'features_section' => ['enabled' => true, 'order' => 4, 'title' => 'Our Features', 'items' => []],
                'our_success' => ['enabled' => true, 'order' => 5, 'stats' => []],
                'testimonials' => ['enabled' => true, 'order' => 6, 'title' => 'What Our Guests Say', 'auto_fetch' => true],
                'vacation_options' => ['enabled' => true, 'order' => 7, 'title' => 'Vacation Options'],
                'travel_news' => ['enabled' => true, 'order' => 8, 'title' => 'Travel News', 'posts_count' => 3],
                'get_quote' => ['enabled' => true, 'order' => 9, 'title' => 'Get a Quote', 'subtitle' => 'Our team is available 24/7'],
            ],
            'about' => [
                'hero' => ['enabled' => true, 'order' => 0, 'title' => 'About Us', 'subtitle' => '', 'bg_image' => null],
                'gallery_ceo' => ['enabled' => true, 'order' => 1, 'content' => ''],
                'news_events' => ['enabled' => true, 'order' => 2, 'title' => 'News & Events'],
                'our_team' => ['enabled' => true, 'order' => 3, 'title' => 'Our Team', 'members' => []],
                'get_quote' => ['enabled' => true, 'order' => 4],
            ],
            'faq' => [
                'hero' => ['enabled' => true, 'order' => 0, 'title' => 'Frequently Asked Questions'],
                'faq_accordion' => ['enabled' => true, 'order' => 1, 'title' => 'General Questions', 'items' => []],
                'destination_faq' => ['enabled' => true, 'order' => 2],
                'get_quote' => ['enabled' => true, 'order' => 3],
            ],
            'contact' => [
                'hotline' => ['enabled' => true, 'order' => 0, 'title' => 'Contact Us', 'phone' => '', 'email' => '', 'address' => ''],
                'get_quote' => ['enabled' => true, 'order' => 1],
            ],
            'success-stories' => [
                'stories_grid' => ['enabled' => true, 'order' => 0, 'title' => 'Success Stories'],
                'review_section' => ['enabled' => true, 'order' => 1],
                'get_quote' => ['enabled' => true, 'order' => 2],
            ],
        ];

        return $defaults[$pageSlug] ?? [];
    }
}
