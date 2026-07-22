<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMenu extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'calendar_id',
        'location_id',
        'special_flag',
        'menu_date',
        'remarks',
        'status',
        'created_by',
    ];

    public function items()
    {
        return $this->hasMany(DailyMenuItem::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
