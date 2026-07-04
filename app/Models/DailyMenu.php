<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMenu extends Model
{
public $timestamps = true;    
protected $fillable = [
        'calendar_id',
        'menu_date',
        'remarks',
        'created_by',
    ];

    public function items()
    {
        return $this->hasMany(DailyMenuItem::class);
    }
}