<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['location_id', 'event_id',  'name', 'status'];

    public function subMenus()
    {
        return $this->hasMany(SubMenu::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function event()
    {
        return $this->belongsTo(EventMaster::class, 'event_id');
    }
}
