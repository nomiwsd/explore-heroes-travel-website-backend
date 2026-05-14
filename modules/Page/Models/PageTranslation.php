<?php
namespace Modules\Page\Models;

use App\BaseModel;

/**
 * Page Translation Model
 * Contains ONLY translatable text fields from Page model
 */
class PageTranslation extends BaseModel
{
    protected $table = 'core_page_translations';

    protected $fillable = [
        // Required for translation relation
        'origin_id',
        'locale',

        // Per-locale URL slug (e.g. /ar/<arabic-slug>)
        'slug',

        // Text fields that need translation
        'title',
        'content',
        'short_desc',
        'banner_title',
    ];

    protected $seo_type = 'page_translation';

    protected $cleanFields = [
        'content'
    ];
}