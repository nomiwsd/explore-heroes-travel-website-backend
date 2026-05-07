<?php
namespace Modules\Review\Models;

use App\BaseModel;

/**
 * Review Translation Model
 * Holds translatable text fields for Review per locale.
 * Mirrors the LocationTranslation pattern.
 */
class ReviewTranslation extends BaseModel
{
    protected $table = 'bc_review_translations';

    protected $fillable = [
        // Translatable text fields
        'title',
        'content',
        'trip_summary',
        'agent_role',
        'origin_id',
        'locale',
    ];

    protected $cleanFields = [
        'content',
    ];
}
