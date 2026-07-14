<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = [
        'guest_type',
        'department_id', 
        'location_id',
        'calendar_id',
        'guest_name',
        'guest_count',
        'guest_remarks',
        'attend_user_id',
        'late_flag',
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

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}