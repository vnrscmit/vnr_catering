<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayOpen extends Model
{
    protected $table = 'day_open';

    protected $fillable = [
        'calender_id',
        'location_id',
        'date',
        'reason',
        'opened_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
