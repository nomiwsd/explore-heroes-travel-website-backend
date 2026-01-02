<?php
namespace Modules\Contact\Models;

use App;
use App\BaseModel;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tour\Models\Tour;

class Contact extends BaseModel
{
    use SoftDeletes;
    protected $table = 'bc_contact';
    protected $fillable = [
        'name',
        'email',
        'message',
        'phone',
        'status',
        'form_type',
        'tour_id',
        'subject',
        'travel_date',
        'number_of_people',
        'special_requirements'
    ];

    /**
     * Get the tour associated with this contact (for quote requests)
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

//    protected $cleanFields = ['message'];
}
