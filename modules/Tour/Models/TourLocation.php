<?php

namespace Modules\Tour\Models;

use App\BaseModel;
use Modules\Location\Models\Location;

class TourLocation extends BaseModel
{
    protected $table = 'bc_tour_locations';

    protected $fillable = [
        'tour_id',
        'map_lat',
        'map_long',
        'address',
        'image_id',
        'location_id',
        'create_user',
        'update_user',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];


    public function tour(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
