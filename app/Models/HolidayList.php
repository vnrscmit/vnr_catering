<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayList extends Model
{
    protected $fillable = [
        'type',
        'location_id',
        'date',
        'calendar_id',
        'remarks',
        'status',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function dayStatus()
    {
        return $this->belongsTo(DayStatus::class, 'calendar_id');
    }
}
