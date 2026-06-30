<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\DayStatus;
use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiMenuController extends Controller
{
    public function menuList()
    {
        $menus = Menu::with(['subMenus' => function ($query) {
            $query->where('status', 1);
        }])->where('status', 1)->get();

        $data = [];

        foreach ($menus as $menu) {

            $subMenus = [];

            foreach ($menu->subMenus as $subMenu) {
                $subMenus[] = [
                    'id'   => $subMenu->id,
                    'name' => $subMenu->name,
                ];
            }

            $data[] = [
                'id'        => $menu->id,
                'menu_name' => $menu->name,
                'sub_menu'  => $subMenus,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'Menu List',
            'data'    => $data,
        ]);
    }

    public function menuListToday()
    {
        $today = Carbon::today()->format('Y-m-d');
        $dayStatus = DayStatus::where('date', $today)->first();
        $dailyMenuList = DailyMenu::with('items.submenu')
            ->where('calendar_id', $dayStatus->id)->where('menu_date', $today)->first();

        $submenus = $dailyMenuList?->items->pluck('submenu.name')->toArray();

        return response()->json([
            'status'  => true,
            'message' => 'Daily Menu List fetched',
            'data'    => $submenus,
        ]);
    }
}
