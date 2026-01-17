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
        // Text fields that need translation
        'name',
        'content',
        'short_description',
        'origin_id',
        'locale'
    ];

    protected $seo_type = 'location_translation';

    protected $cleanFields = [
        'content'
    ];
}
