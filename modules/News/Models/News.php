<?php

namespace Modules\News\Models;

use App\BaseModel;
use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\SEO;
use Modules\Review\Models\Review;
use Modules\Location\Models\Location;

class News extends BaseModel
{
    use SoftDeletes;

    protected $table = 'core_news';
    protected $fillable = [
        'title',
        'content',
        'short_desc',
        'excerpt',
        'status',
        'is_featured',
        'cat_id',
        'location_id',
        'image_id',
        'og_image_id',
        'image_alt',
        'gallery',
        'related_posts',
        'author_id',
        'author_bio',
        'reading_time',
        'meta_title',
        'meta_desc',
        'meta_keywords',
    ];
    protected $slugField = 'slug';
    protected $slugFromField = 'title';
    protected $seo_type = 'news';
    public $type = 'news';

    protected $casts = [
        'related_posts' => 'array',
    ];

    protected $sitemap_type = 'page';

    public function getDetailUrlAttribute()
    {
        return url('news-' . $this->slug);
    }

    public function geCategorylink()
    {
        return route('news.category.index', ['slug' => $this->slug]);
    }

    public function cat()
    {
        return $this->belongsTo(NewsCategory::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function relatedPosts()
    {
        $relatedIds = $this->related_posts ?? [];
        if (empty($relatedIds)) {
            return collect([]);
        }
        return self::whereIn('id', $relatedIds)->where('status', 'publish')->get();
    }

    public function getAutoRelatedPosts($limit = 6)
    {
        return self::where('id', '!=', $this->id)
            ->where('status', 'publish')
            ->where(function($query) {
                if ($this->cat_id) {
                    $query->where('cat_id', $this->cat_id);
                }
                if ($this->location_id) {
                    $query->orWhere('location_id', $this->location_id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getFeatured($limit = 6)
    {
        return self::where('status', 'publish')
            ->where('is_featured', 1)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getAll()
    {
        return self::with('cat')->get();
    }

    public function getDetailUrl($locale = false)
    {
        return url(app_get_locale(false, false, '/') . config('news.news_route_prefix') . "/" . $this->slug);
    }

    public function category()
    {
        $catename = $this->belongsTo("Modules\News\Models\NewsCategory", "cat_id", "id");
        return $catename;
    }

    public function getTags()
    {
        $tags = NewsTag::where('news_id', $this->id)->get();
        $tag_ids = [];
        if (!empty($tags)) {
            foreach ($tags as $key => $value) {
                $tag_ids[] = $value->tag_id;
            }
        }
        return Tag::whereIn('id', $tag_ids)->with('translation')->get();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, NewsTag::class, 'news_id', 'tag_id')->with('translation');
    }

    public function tours()
    {
        return $this->hasMany(NewsTour::class);
    }

    public static function searchForMenu($q = false)
    {
        $query = static::select('id', 'title as name');
        if (strlen($q)) {

            $query->where('title', 'like', "%" . $q . "%");
        }
        $a = $query->orderBy('id', 'desc')->limit(10)->get();
        return $a;
    }

    public function saveTag($tags_name, $tag_ids)
    {

        if (empty($tag_ids))
            $tag_ids = [];
        $tag_ids = array_merge(Tag::saveTagByName($tags_name), $tag_ids);
        $tag_ids = array_filter(array_unique($tag_ids));
        // Delete unused
        NewsTag::whereNotIn('tag_id', $tag_ids)->where('news_id', $this->id)->delete();
        //Add
        NewsTag::addTag($tag_ids, $this->id);
    }

    static public function getSeoMetaForPageList()
    {
        $meta['seo_title'] = setting_item_with_lang("news_page_list_seo_title", false, null) ?? setting_item_with_lang("news_page_list_title", false, null) ?? __("News");
        $meta['seo_desc'] = setting_item_with_lang("news_page_list_seo_desc");
        $meta['seo_image'] = setting_item("news_page_list_seo_image", null) ?? setting_item("news_page_list_banner", null);
        $meta['seo_share'] = setting_item_with_lang("news_page_list_seo_share");
        $meta['full_url'] = url()->current();
        return $meta;
    }

    public function getEditUrl()
    {
        $lang = $this->lang ?? setting_item("site_locale");
        return route('news.admin.edit', ['id' => $this->id, "lang" => $lang]);
    }

    public function dataForApi($forSingle = false)
    {
        $translation = $this->translate();
        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $translation->title,
            'content' => $translation->content,
            'excerpt' => $this->excerpt ?? $this->getExcerptFromContent(),
            'image_id' => $this->image_id,
            'image_url' => get_file_url($this->image_id, 'full'),
            'image_alt' => $this->image_alt ?? $translation->title,
            'og_image_url' => $this->og_image_id ? get_file_url($this->og_image_id, 'full') : get_file_url($this->image_id, 'full'),
            'is_featured' => $this->is_featured,
            'reading_time' => $this->reading_time ?? $this->calculateReadingTime(),
            'category' => NewsCategory::selectRaw("id,name,slug")->find($this->cat_id) ?? null,
            'location' => $this->location ? [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'slug' => $this->location->slug,
            ] : null,
            'created_at' => display_date($this->created_at),
            'publish_date' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'author' => $this->author ? [
                'id' => $this->author->id,
                'display_name' => $this->author->getDisplayName(),
                'avatar_url' => $this->author->getAvatarUrl(),
                'bio' => $this->author_bio ?? null,
            ] : null,
            'url' => $this->getDetailUrl(),
            'tags' => $this->getTags()->map(function($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name ?? $tag->translate()->name,
                    'slug' => $tag->slug,
                ];
            }),
        ];

        if ($forSingle) {
            // Add related posts for single view
            $relatedPosts = $this->related_posts ? $this->relatedPosts() : $this->getAutoRelatedPosts(6);
            $data['related_posts'] = $relatedPosts->map(function($post) {
                return [
                    'id' => $post->id,
                    'slug' => $post->slug,
                    'title' => $post->translate()->title,
                    'image_url' => get_file_url($post->image_id, 'full'),
                    'image_alt' => $post->image_alt ?? $post->translate()->title,
                    'publish_date' => $post->created_at ? $post->created_at->format('Y-m-d') : null,
                    'author' => $post->author ? [
                        'display_name' => $post->author->getDisplayName(),
                        'avatar_url' => $post->author->getAvatarUrl(),
                    ] : null,
                ];
            });

            // Add next/prev posts
            $nextPost = $this->getNextPost();
            $prevPost = $this->getPrevPost();
            $data['next_post'] = $nextPost ? [
                'id' => $nextPost->id,
                'slug' => $nextPost->slug,
                'title' => $nextPost->translate()->title,
            ] : null;
            $data['prev_post'] = $prevPost ? [
                'id' => $prevPost->id,
                'slug' => $prevPost->slug,
                'title' => $prevPost->translate()->title,
            ] : null;
        }

        return $data;
    }

    protected function getExcerptFromContent($length = 160)
    {
        $content = strip_tags($this->translate()->content ?? '');
        return strlen($content) > $length ? substr($content, 0, $length) . '...' : $content;
    }

    protected function calculateReadingTime()
    {
        $wordCount = str_word_count(strip_tags($this->translate()->content ?? ''));
        return max(1, ceil($wordCount / 200)); // 200 words per minute
    }

    public function getNextPost()
    {
        return News::where('id', '>', $this->id)->where('status', 'publish')->first();
    }

    public function getPrevPost()
    {
        return News::where('id', '<', $this->id)->where('status', 'publish')->orderByDesc('id')->first();
    }

    public static function getModelName()
    {
        return __("News");
    }

    public function getReviewEnable()
    {
        return setting_item("news_enable_review", 0);
    }

    public function getReviewApproved()
    {
        return setting_item("news_review_approved", 0);
    }

    public function update_service_rate()
    {
        // No action
    }

    public function getReviewData()
    {
        $review = app(Review::class);
        $reviewData = $review::getTotalViewAndRateAvg($this->id, $this->type);
        return $reviewData;
    }

    public static function getReviewStats()
    {
        return [];
    }

}
