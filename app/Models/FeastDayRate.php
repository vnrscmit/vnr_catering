<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeastDayRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'rate_master_id',
        'feast_day_calendar_id',
        'feast_date',
        'member_rate',
        'non_member_rate',
        'guest_rate',
        'status',
    ];

    protected $casts = [
        'feast_date' => 'date',
        'member_rate' => 'decimal:2',
        'non_member_rate' => 'decimal:2',
        'guest_rate' => 'decimal:2',
    ];

    public function rateMaster()
    {
        return $this->belongsTo(RateMaster::class);
    }

    public function feastDay()
    {
        return $this->belongsTo(DayStatus::class, 'feast_day_calendar_id');
    }
}
