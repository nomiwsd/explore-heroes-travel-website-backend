<?php
namespace Modules\Review\Models;

use App\BaseModel;

class ReviewMeta extends BaseModel
{
    protected $table = 'bc_review_meta';
    protected $fillable = [
        'review_id',
        'object_id',
        'object_model',
        'name',
        'val',
    ];
}
