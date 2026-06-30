<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAbsent extends Model
{
    protected $fillable = [
        'calendar_id',
        'user_id',
        'absent_flag',
        'absent_remarks',
        'override_flag',
        'override_remarks',
        'status',
    ];

    public function calendar()
    {
        return $this->belongsTo(DayStatus::class, 'calendar_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}