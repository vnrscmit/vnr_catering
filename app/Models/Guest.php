<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = [
        'guest_type',
        'date',
        'department_id',
        'location_id',
        'event_id',
        'calendar_id',
        'guest_name',
        'guest_count',
        'guest_remarks',
        'attend_user_id',
        'late_flag',
        'created_by',
        'status',
    ];



    public function calendar()
    {
        return $this->belongsTo(DayStatus::class, 'calendar_id');
    }

    public function attendUser()
    {
        return $this->belongsTo(User::class, 'attend_user_id');
    }

     public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function event()
    {
        return $this->belongsTo(EventMaster::class, 'event_id');
    }
}
