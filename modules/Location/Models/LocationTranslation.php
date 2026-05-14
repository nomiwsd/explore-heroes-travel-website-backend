<?php
namespace Modules\Location\Models;

use App\BaseModel;

/**
 * Location Translation Model
 * Contains ONLY translatable text fields from Location model
 */
class LocationTranslation extends BaseModel
{
    protected $table = 'bc_location_translations';

    protected $fillable = [
        // Required for translation lookup/save
        'origin_id',
        'locale',

        // Per-locale URL slug (e.g. /ar/destinations/<arabic-slug>)
        'slug',

        // Text fields that need translation
        'name',
        'content',
        'short_description',
    ];

    protected $seo_type = 'location_translation';

    protected $cleanFields = [
        'content'
    ];
}
