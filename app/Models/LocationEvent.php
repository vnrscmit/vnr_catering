<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationEvent extends Model
{
    protected $table = 'location_event';

    public $timestamps = true;

    protected $fillable = [
        'location_id',
        'event_id',
        'status',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'status' => 'integer',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function event()
    {
        return $this->belongsTo(EventMaster::class);
    }
}