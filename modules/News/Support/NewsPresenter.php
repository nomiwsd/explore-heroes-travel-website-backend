<?php

namespace Modules\News\Support;

use Illuminate\Support\Facades\Schema;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;
use Modules\News\Models\NewsTranslation;

/**
 * Shared presentation helpers for the public blog API.
 */
class NewsPresenter
{
    protected static $columnCache = [];

    public static function hasTranslationColumn(string $column): bool
    {
        if (!array_key_exists($column, static::$columnCache)) {
            try {
                static::$columnCache[$column] = Schema::hasColumn('core_news_translations', $column);
            } catch (\Throwable $e) {
                static::$columnCache[$column] = false;
            }
        }
        return static::$columnCache[$column];
    }

    public static function looksLikeEmail($value): bool
    {
        return is_string($value) && strpos($value, '@') !== false;
    }

    /**
     * BL-01: public author display name. Never an email address.
     * display name (business / first+last) -> first+last -> name -> site name -> "Explore Heroes Team".
     */
    public static function authorName($user): string
    {
        if ($user) {
            $first = trim((string) ($user->first_name ?? ''));
            $last = trim((string) ($user->last_name ?? ''));
            $candidates = [
                $user->business_name ?? null,
                trim($first . ' ' . $last),
                $user->getAttributes()['name'] ?? null,
            ];
            foreach ($candidates as $c) {
                $c = is_string($c) ? trim($c) : '';
                if ($c !== '' && !static::looksLikeEmail($c)) {
                    return $c;
                }
            }
        }
        return static::fallbackAuthorName();
    }

    public static function fallbackAuthorName(): string
    {
        $site = function_exists('setting_item') ? trim((string) setting_item('site_title')) : '';
        if ($site !== '' && !static::looksLikeEmail($site) && stripos($site, 'booking core') === false) {
            return $site;
        }
        return 'Explore Heroes Team';
    }

    public static function mediaPath($imageId)
    {
        if (!$imageId) {
            return null;
        }
        $media = \Modules\Media\Models\MediaFile::find($imageId);
        if (!$media) {
            return null;
        }
        $path = '/uploads/' . ltrim($media->file_path, '/');
        return str_replace('/uploads/uploads/', '/uploads/', $path);
    }

    /**
     * Public author block. Never includes email.
     */
    public static function author($userId, $bio = null)
    {
        if (!$userId) {
            return null;
        }
        $user = \App\User::find($userId);
        if (!$user) {
            return null;
        }
        $author = [
            'id' => $user->id,
            'display_name' => static::authorName($user),
            'avatar_url' => static::mediaPath($user->avatar_id ?? null),
        ];
        if ($bio !== false) {
            $author['bio'] = $bio ?: ($user->bio ?? null);
        }
        return $author;
    }

    public static function iso($date)
    {
        if (empty($date)) {
            return null;
        }
        try {
            if ($date instanceof \DateTimeInterface) {
                return \Carbon\Carbon::instance($date)->toIso8601String();
            }
            return \Carbon\Carbon::parse($date)->toIso8601String();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function publishedAt($post)
    {
        return static::iso($post->publish_date ?: $post->created_at);
    }

    public static function needsTranslation($lang): bool
    {
        return $lang && !is_default_lang($lang);
    }

    /**
     * @return array<int,NewsTranslation> origin_id => translation
     */
    public static function translationsFor(array $postIds, $lang): array
    {
        if (!static::needsTranslation($lang) || empty($postIds)) {
            return [];
        }
        $out = [];
        foreach (NewsTranslation::whereIn('origin_id', $postIds)->where('locale', $lang)->get() as $tr) {
            $out[(int) $tr->origin_id] = $tr;
        }
        return $out;
    }

    public static function category($catId, $lang)
    {
        if (!$catId) {
            return null;
        }
        $cat = NewsCategory::find($catId);
        if (!$cat) {
            return null;
        }
        $tr = static::needsTranslation($lang) ? $cat->forceTranslate($lang) : null;
        return [
            'id' => $cat->id,
            'name' => ($tr && $tr->name) ? $tr->name : $cat->name,
            'slug' => $cat->slug,
        ];
    }

    /**
     * Translated value with origin fallback (empty translation => origin).
     */
    public static function tr($translation, $post, string $field, $originField = null)
    {
        $originField = $originField ?: $field;
        if ($translation && isset($translation->$field) && $translation->$field !== '' && $translation->$field !== null) {
            return $translation->$field;
        }
        return $post->$originField ?? null;
    }

    /**
     * BL-04: translated featured-image alt text.
     */
    public static function imageAlt($post, $translation = null)
    {
        if ($translation && static::hasTranslationColumn('image_alt') && !empty($translation->image_alt)) {
            return $translation->image_alt;
        }
        return $post->image_alt ?? null;
    }

    /**
     * BL-04: translated author bio.
     */
    public static function authorBio($post, $translation = null)
    {
        if ($translation && static::hasTranslationColumn('author_bio') && !empty($translation->author_bio)) {
            return $translation->author_bio;
        }
        return $post->author_bio ?? null;
    }

    /**
     * Card shape used by related posts (BL-03).
     */
    public static function relatedCard($post, $lang, $translation = null): array
    {
        return [
            'id' => $post->id,
            'title' => static::tr($translation, $post, 'title'),
            'slug' => static::tr($translation, $post, 'slug'),
            'excerpt' => static::tr($translation, $post, 'excerpt'),
            'image_url' => static::mediaPath($post->image_id),
            'image_alt' => static::imageAlt($post, $translation),
            'featured_image_alt' => static::imageAlt($post, $translation),
            'cat_id' => $post->cat_id,
            'category' => static::category($post->cat_id, $lang),
            'publish_date' => $post->publish_date ? $post->publish_date->format('Y-m-d') : ($post->created_at ? $post->created_at->format('Y-m-d') : null),
            'published_at' => static::publishedAt($post),
            'created_at' => $post->created_at,
            'updated_at' => static::iso($post->updated_at),
        ];
    }
}
