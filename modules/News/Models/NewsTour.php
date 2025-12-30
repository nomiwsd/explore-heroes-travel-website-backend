<?php
namespace Modules\News\Models;

use App\BaseModel;
use Modules\Tour\Models\Tour;
use Modules\News\Models\News;

class NewsTour extends BaseModel
{
    protected $table = 'core_news_tours';
    protected $fillable = [
        'news_id',
        'tour_id'
    ];

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

}
