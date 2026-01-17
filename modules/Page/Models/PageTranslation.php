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
        // Text fields that need translation
        'title',
        'content',
        'short_desc',
        'banner_title',

        // Required for translation relation
        'origin_id',
        'locale',
    ];

    protected $seo_type = 'page_translation';

    protected $cleanFields = [
        'content'
    ];
}