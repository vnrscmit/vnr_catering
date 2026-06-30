<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMenuItem extends Model
{
    protected $fillable = [
        'daily_menu_id',
        'menu_id',
        'submenu_id',
        'quantity',
    ];

    public function dailyMenu()
    {
        return $this->belongsTo(DailyMenu::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function submenu()
    {
        return $this->belongsTo(Submenu::class);
    }
}