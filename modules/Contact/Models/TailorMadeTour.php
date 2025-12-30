<?php

namespace Modules\Contact\Models;

use App;
use App\BaseModel;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class TailorMadeTour extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_tailor_made_tour';
    protected $fillable = [
        'salutation', 'first_name', 'last_name', 'email', 'phone', 'country', 'age_13_17', 'age_18_25', 'age_26_35', 'age_36_45', 'age_46_55', 'age_56_69', 'age_70_above', 'age_below_2', 'age_3_7', 'age_8_12', 'interests', 'type_of_accommodation', 'budget_currency', 'budget_per_person', 'when_to_go', 'trip_date', 'no_of_nights_known', 'roughly_month', 'roughly_year', 'no_of_nights_unknown', 'comments'
    ];

//    protected $cleanFields = ['message'];
}
