<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SubMenu;
use App\Models\Menu;
use App\Models\DailyMenu;


class DailyMenuItem extends Model
{
    public $timestamps = true;
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
        return $this->belongsTo(SubMenu::class);
    }
}
