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
        'special_requirements',
        'notes',
        'destination_name'
    ];
    
    // Available statuses for form submissions
    const STATUS_NEW = 'new';
    const STATUS_READ = 'read';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_QUOTED = 'quoted';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archived';
    
    public static function getStatuses()
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_READ => 'Read',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_QUOTED => 'Quoted',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    /**
     * Get the tour associated with this contact (for quote requests)
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

//    protected $cleanFields = ['message'];
}
