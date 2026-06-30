<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'calendar_id',
        'user_id',
        'absent_flag',
        'created_by',
        'remarks',
        'status',
    ];

    protected $casts = [
        'absent_flag' => 'boolean',
        'status'      => 'boolean',
    ];

    public function calendar()
    {
        return $this->belongsTo(DayStatus::class, 'calendar_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}