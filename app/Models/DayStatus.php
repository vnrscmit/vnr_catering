<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayStatus extends Model
{
    protected $fillable = [
        'date',
        'day_name',
        'month',
        'day',
        'year',
        'holiday_flag',
        'sunday_flag',
        'open_flag',
        'open_remarks',
        'open_user_id',
        'closed_flag',
        'closed_remarks',
        'remarks',
        'status',
    ];

    public function openUser()
    {
        return $this->belongsTo(User::class, 'open_user_id');
    }
}