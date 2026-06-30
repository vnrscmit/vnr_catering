<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyParameter extends Model
{
    protected $fillable = [
        'location_id',
        'attendance_out_time',
        'lunch_out_time',
        'max_day_show',
        'status',
    ];

    protected $casts = [
        'attendance_out_time' => 'datetime:H:i:s',
        'lunch_out_time'      => 'datetime:H:i:s',
        'max_day_show'        => 'integer',
        'status'              => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}