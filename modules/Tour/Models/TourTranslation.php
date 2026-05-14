<?php
namespace Modules\Tour\Models;

use App\BaseModel;

/**
 * Tour Translation Model
 * Contains ONLY translatable text fields from Tour model
 */
class TourTranslation extends BaseModel
{
    protected $table = 'bc_tour_translations';

    protected $fillable = [
        // Required for translation lookup/save
        'origin_id',
        'locale',

        // Per-locale URL slug (e.g. /ar/tours/<arabic-slug>)
        'slug',

        // Text fields that need translation
        'title',
        'short_desc',
        'address',

        // JSON/Array content fields
        'faqs',           // FAQ questions and answers
        'itinerary',      // Day-by-day itinerary content
        'surrounding',    // Surrounding area description
        'inclusions',     // What's included list
        'exclusions',     // What's not included list

        // Policy text fields
        'conditions',
        'cancellation_policy',
        'child_policy',
        'payment_terms',
    ];

    protected $slugField = false;
    protected $seo_type = 'tour_translation';

    protected $cleanFields = [
        'conditions',
        'cancellation_policy',
        'child_policy',
        'payment_terms',
    ];

    protected $casts = [
        'faqs' => 'array',
        'itinerary' => 'array',
        'surrounding' => 'array',
        'inclusions' => 'array',
        'exclusions' => 'array',
    ];

    public function getSeoType()
    {
        return $this->seo_type;
    }

    public function getRecordRoot()
    {
        return $this->belongsTo(Tour::class, 'origin_id');
    }
}
