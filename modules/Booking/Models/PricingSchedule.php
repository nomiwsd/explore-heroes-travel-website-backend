<?php

namespace Modules\Booking\Models;

use App\BaseModel;

class PricingSchedule extends BaseModel
{
    protected $table = 'bc_pricing_schedule';

    protected $fillable = [
        'start_date',
        'end_date',
        'months',
        'day_of_week',
        'priority',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];
}
