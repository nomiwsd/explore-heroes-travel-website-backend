<?php
namespace Modules\News\Models;

use App\BaseModel;

/**
 * News Translation Model
 * Contains ONLY translatable text fields from News model
 */
class NewsTranslation extends BaseModel
{
    protected $table = 'core_news_translations';

    protected $fillable = [
        // Required for translation lookup/save
        'origin_id',
        'locale',

        // Per-locale URL slug (e.g. /ar/blogs/<arabic-slug>)
        'slug',

        // Text fields that need translation
        'title',
        'content',
        'short_desc',
        'excerpt',
    ];

    protected $seo_type = 'news_translation';

    protected $cleanFields = [
        'content'
    ];
}
