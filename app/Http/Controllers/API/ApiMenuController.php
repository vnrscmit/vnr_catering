<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\DailyMenuItem;
use App\Models\DayStatus;
use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiMenuController extends Controller
{
    // public function menuList()

    // {
    //     $menus = Menu::with(['subMenus' => function ($query) {
    //         $query->where('status', 1);
    //     }])->where('status', 1)->get();

    //     $data = [];

    //     foreach ($menus as $menu) {

    //         $subMenus = [];

    //         foreach ($menu->subMenus as $subMenu) {
    //             $subMenus[] = [
    //                 'id'   => $subMenu->id,
    //                 'name' => $subMenu->name,
    //             ];
    //         }

    //         $data[] = [
    //             'id'        => $menu->id,
    //             'menu_name' => $menu->name,
    //             'sub_menu'  => $subMenus,
    //         ];
    //     }

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Menu List',
    //         'data'    => $data,
    //     ]);
    // }


    public function menuListDateWise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationId = $request->location_id;

        $today = Carbon::today()->toDateString();

        $dayStatus = DayStatus::select(
            'day_statuses.id',
            'day_statuses.date',
            'day_statuses.day_name',
            'day_statuses.month',
            'day_statuses.day',
            'day_statuses.year',
            DB::raw('COALESCE(daily_menus.status, 0) as status')
        )
            ->leftJoin('daily_menus', function ($join) use ($locationId) {
                $join->on('daily_menus.calendar_id', '=', 'day_statuses.id')
                    ->where('daily_menus.location_id', '=', $locationId);
            })
            ->where('day_statuses.open_flag', 1)
            ->whereDate('day_statuses.date', '>=', $today)
            ->where('day_statuses.location_id', $locationId)
            ->orderBy('day_statuses.date', 'asc')
            ->limit(7)
            ->get();

        foreach ($dayStatus as $data) {
        }

        return response()->json([
            'status'  => true,
            'message' => 'Menu dates fetched successfully.',
            'data'    => $dayStatus,
        ]);
    }

    public function menuList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'calendar_id' => 'required|exists:day_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationId = $request->location_id;
        $calendarId = $request->calendar_id;

        $dayStatus = DayStatus::where('location_id', $locationId)
            ->where('id', $calendarId)
            ->first();

        $dailyMenu = DailyMenu::with('items')
            ->where('calendar_id', $calendarId)
            ->where('location_id', $locationId)
            ->whereDate('menu_date', $dayStatus->date)
            ->first();

        $selectedSubmenuIds = $dailyMenu
            ? $dailyMenu->items->pluck('submenu_id')->toArray()
            : [];

        $menus = Menu::with([
            'subMenus' => function ($query) {
                $query->where('status', 1);
            }
        ])
            ->where('status', 1)
            ->get();

        $data = [];

        foreach ($menus as $menu) {

            $subMenus = [];

            foreach ($menu->subMenus as $subMenu) {

                $subMenus[] = [
                    'id'     => $subMenu->id,
                    'name'   => $subMenu->name,
                    'status' => in_array($subMenu->id, $selectedSubmenuIds) ? 1 : 0,
                ];
            }

            $data[] = [
                'id'        => $menu->id,
                'menu_name' => $menu->name,
                'spcial_flag' => $menu->special_flag,
                'sub_menu'  => $subMenus,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'Menu List fetched successfully.',
            'data'    => $data,
        ]);
    }


    public function storeOrUpdateMenu(Request $request)
    {
        // Handle JSON string menus
        if (is_string($request->menus)) {
            $request->merge([
                'menus' => json_decode($request->menus, true)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'calendar_id' => 'required|exists:day_statuses,id',
            'status'      => 'required|in:0,1',
            'menus'       => 'required|array|min:1',
            'menus.*.menu_id'     => 'required|exists:menus,id',
            'menus.*.sub_menu_id' => 'required|exists:sub_menus,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            $dayStatus = DayStatus::where('location_id', $request->location_id)
                ->where('id', $request->calendar_id)
                ->first();

            if (!$dayStatus) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid calendar selected.',
                ], 404);
            }

            // Create or Update Daily Menu
            $dailyMenu = DailyMenu::updateOrCreate(
                [
                    'calendar_id' => $request->calendar_id,
                    'location_id' => $request->location_id,
                ],
                [
                    'menu_date'  => $dayStatus->date,
                    'remarks'    => $request->remarks,
                    'status'     => $request->status,
                    'created_by' => auth()->id(),
                ]
            );

            // Delete old menu items
            DailyMenuItem::where('daily_menu_id', $dailyMenu->id)->delete();

            // Prepare bulk insert data
            $menuItems = [];

            foreach ($request->menus as $menu) {

                $menuItems[] = [
                    'daily_menu_id' => $dailyMenu->id,
                    'menu_id'       => $menu['menu_id'],
                    'submenu_id'    => $menu['sub_menu_id'],
                    'quantity'      => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            // Insert menu items
            if (!empty($menuItems)) {
                DailyMenuItem::insert($menuItems);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Daily menu saved successfully.',
                'data'    => $dailyMenu,
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
