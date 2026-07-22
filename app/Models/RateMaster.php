<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'type',
        'effective_from_date',
        'effective_to_date',
        'effective_from_calendar_id',
        'effective_to_calendar_id',
        'member_rate',
        'non_member_rate',
        'min_day_rate',
        'guest_rate',
        'created_by',
        'status',
    ];

    protected $casts = [
        'effective_from_date' => 'date',
        'effective_to_date'   => 'date',
    ];
}
