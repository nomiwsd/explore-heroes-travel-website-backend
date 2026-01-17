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
        // Text fields that need translation
        'title',
        'content',
        'short_desc',
        'excerpt',
        'origin_id',
        'locale'
    ];

    protected $seo_type = 'news_translation';

    protected $cleanFields = [
        'content'
    ];
}
