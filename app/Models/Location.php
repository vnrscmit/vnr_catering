<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'short_code', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'location_id');
    }
}
