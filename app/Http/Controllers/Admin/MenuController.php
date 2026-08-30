<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\DayStatus;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use App\Models\DailyMenu;
use App\Models\Location;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\LocationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }


    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->role == 'Canteen Administrator') {
            $locationId = Location::where('status', 1)->where('id',  $user->location_id)->value('id');
            $eventList = LocationEvent::with('event')
                ->where('location_id', $user->location_id)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } elseif ($user->role == 'Super Admin') {
            $locationId = Location::where('status', 1)->where('id',  $user->location_id)->value('id');
            $eventList = LocationEvent::with('event')
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } else {
            return redirect()->back()->with('error', 'You do not have permission to access this page.');
        }

        if ($request->ajax()) {

            // $query = Menu::with('subMenus', 'location')
            //     ->orderBy('name', 'ASC');
            $query = Menu::with('subMenus', 'location', 'event')
                ->orderByRaw("
        (SELECT seq_no FROM event_masters WHERE event_masters.id = menus.event_id) ASC,
        CASE
            WHEN FIELD(menus.name,
                'Starters',
                'Refresher',
                'Vegetable',
                'Dal',
                'Roti',
                'Rice',
                'Accompaniments',
                'Dessert'
            ) = 0 THEN 999
            ELSE FIELD(menus.name,
                'Starters',
                'Refresher',
                'Vegetable',
                'Dal',
                'Roti',
                'Rice',
                'Accompaniments',
                'Dessert'
            )
        END
    ")
                ->orderBy('menus.name');


            if ($user->role == 'Canteen Administrator') {
                $query->where('location_id', $user->location_id);
            } elseif ($user->role == 'Super Admin') {
                $locationList = Location::where('status', 1)->get();
            } else {
                return redirect()->back()->with('error', 'You do not have permission to access this page.');
            }
            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('submenus', function ($row) {
                    if ($row->subMenus->isNotEmpty()) {
                        $submenuNames = [];
                        foreach ($row->subMenus as $submenu) {
                            $name = $submenu->name;
                            if ($submenu->special_flag == 1) {
                                $name .= ' <i class="fa fa-star text-warning" title="Special"></i>';
                            }
                            $submenuNames[] = $name;
                        }
                        $submenus = implode(', ', $submenuNames);
                    } else {
                        $submenus = ' ';
                    }

                    // Add the "+" button after submenus
                    return $submenus . ' 
    <a href="' . route('admin.submenus.create', $row->id) . '" 
       class="">
        Add New
    </a>';
                })
                ->addColumn('event', function ($row) {
                    return $row->event->name ?? '';
                })
                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-primary"><i class="fa fa-check"></i> Active</span>'
                        : '<span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>';
                })

                ->addColumn('action', function ($row) {
                    return '
        <button type="button"
            class="btn btn-warning btn-sm editMenuBtn"
            data-bs-toggle="modal"
            data-bs-target="#editMenuModal"
            data-id="' . $row->id . '"
            data-location_id="' . $row->location_id . '"
             data-event_id="' . $row->event_id . '"
            data-name="' . e($row->name) . '"
            data-status="' . $row->status . '">
            <i class="fa fa-edit"></i>
        </button>';
                })

                ->rawColumns(['submenus', 'status', 'action', 'location', 'event'])
                ->make(true);
        }

        return view('admin.menu.index', compact('eventList', 'locationId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                Rule::unique('menus')->where(function ($query) use ($request) {
                    return $query->where('location_id', $request->location_id)->where('event_id', $request->event_id);
                }),
            ],
            'location_id' => 'required|exists:locations,id',
            'event_id' => 'required|exists:event_masters,id',
            'status' => 'required'
        ]);

        Menu::create([
            'location_id' => $request->location_id,
            'event_id' => $request->event_id,
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Menu added successfully.'
        ]);
    }

    public function update(MenuRequest $request, $id): RedirectResponse
    {
        $menu = Menu::findOrFail($id);
        $menu->update($request->validated());

        return back()->with('success', 'Menu updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully!');
    }

    public function menuListToday(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {

            $query = DailyMenu::with([
                'location',
                'items.menu',
                'items.submenu',
                'event',
            ]);

            // Role Wise Filter
            if ($user->role == 'Canteen Incharge' || $user->role == 'Canteen Administrator') {
                $query->where('location_id', $user->location_id);
            }

            $data = $query->select('daily_menus.*')
                ->join('event_masters', 'daily_menus.event_id', '=', 'event_masters.id')
                ->orderBy('daily_menus.menu_date', 'desc')
                ->orderBy('event_masters.seq_no', 'asc')
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('event', function ($row) {
                    return $row->event->name ?? '-';
                })

                ->editColumn('menu_date', function ($row) {
                    return Carbon::parse($row->menu_date)->format('d-m-Y');
                })

                ->addColumn('menu_items', function ($row) {
                    if ($row->items->isNotEmpty()) {
                        $submenuNames = [];
                        foreach ($row->items as $item) {
                            if ($item->submenu) {
                                $name = $item->submenu->name;
                                if ($item->submenu->special_flag == 1) {
                                    $name .= ' <i class="fa fa-star text-warning" title="Special"></i>';
                                }
                                $submenuNames[] = $name;
                            }
                        }
                        $submenus = implode(', ', $submenuNames);
                    } else {
                        $submenus = '<span class="text-muted">No items</span>';
                    }

                    return $submenus;
                })

                ->addColumn('action', function ($row) {

                    // Past date => No Edit/Delete
                    if (Carbon::parse($row->menu_date)->lt(Carbon::today())) {
                        return '<span class="text-muted">Past Date</span>';
                    }

                    return '
                <a href="' . route('today-menu.edit', $row->id) . '" class="btn btn-warning btn-sm">
                    <i class="fa fa-edit"></i>
                </a>

                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteModal"
                    data-id="' . $row->id . '">
                    <i class="fa fa-trash"></i>
                </button>';
                })

                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-primary">Publish</span>'
                        : '<span class="badge bg-warning">Draft</span>';
                })

                ->rawColumns(['menu_items', 'action', 'status']) // <-- Added 'menu_items' here
                ->make(true);
        }

        return view('admin.todaymenu.index');
    }

    public function createTodayMenu()
    {
        $user = Auth::user();

        if (in_array($user->role, ['Member', 'Non Member', 'Canteen President'])) {
            abort(403, 'This action is not available for this role.');
        }
        if ($user->role == 'Canteen Incharge' || $user->role == 'Canteen Administrator') {
            $locationId = Location::where('status', 1)->where('id',  $user->location_id)->value('id');

            $eventList = LocationEvent::with('event')
                ->where('location_id', $user->location_id)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } else {
            $locationId = Location::where('status', 1)->where('id',  $user->location_id)->value('id');

            $eventList = LocationEvent::with('event')
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        }

        $menus = Menu::with('subMenus')
            ->where('location_id', $user->location_id)
            ->orderByRaw("
        CASE
            WHEN FIELD(name,
                'Starters',
                'Refresher',
                'Vegetable',
                'Dal',
                'Roti',
                'Rice',
                'Accompaniments',
                'Dessert'
            ) = 0 THEN 999
            ELSE FIELD(name,
                'Starters',
                'Refresher',
                'Vegetable',
                'Dal',
                'Rice',
                'Roti',
                 'Accompaniments',
                'Dessert'
            )
        END
    ")
            ->get();

        return view('admin.todaymenu.create', compact('menus', 'locationId', 'eventList'));
    }
    public function storeTodayMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_date' => 'required|date',
            'submenu_id' => 'required|array',
            'submenu_id.*' => 'array',
            'submenu_id.*.*' => 'exists:sub_menus,id',
            'location_id' => 'required|exists:locations,id',
            'event_id' => 'required|exists:event_masters,id',
            'special_flag' => 'required|in:0,1',
            'action' => 'required|in:1,2',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator->errors())
                ->withInput();
        }

        $dayStatus = DayStatus::where('date', $request->menu_date)->where('location_id', $request->location_id)->first();

        if (!$dayStatus) {
            return back()->withErrors([
                'menu_date' => 'Day Status not found for selected date.'
            ]);
        }

        $checkExistingMenu = DailyMenu::where('menu_date', $request->menu_date)->where('location_id', $request->location_id)->where('event_id', $request->event_id)->first();

        if ($checkExistingMenu) {
            return back()->withErrors([
                'menu_date' => 'Daily Menu already exists for the selected date.'
            ]);
        }

        $dailyMenu = DailyMenu::create([
            'calendar_id' => $dayStatus->id,
            'location_id' => $request->location_id,
            'event_id' => $request->event_id,
            'special_flag' => $request->special_flag,
            'menu_date' => $request->menu_date,
            'remarks' => $request->remarks,
            'status' => $request->action,
            'created_by' => auth()->id(),
        ]);

        foreach ($request->submenu_id as $menuId => $submenus) {

            if (empty($submenus)) {
                continue;
            }
            foreach ($submenus as $submenuId) {
                $dailyMenu->items()->create([
                    'menu_id'    => $menuId,
                    'submenu_id' => $submenuId,
                ]);
            }
        }

        return redirect()->route('today-menu.index')->with('success', 'Daily Menu created successfully!');
    }

    public function editTodayMenu($id)
    {
        $user = Auth::user();

        $dailyMenu = DailyMenu::with('items')->findOrFail($id);

        // Get event list based on user role
        if ($user->role == 'Canteen Administrator') {
            $locationId = Location::where('status', 1)->where('id', $user->location_id)->value('id');
            $eventList = LocationEvent::with('event')
                ->where('location_id', $user->location_id)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } elseif ($user->role == 'Super Admin') {
            $locationId = Location::where('status', 1)->where('id', $user->location_id)->value('id');
            $eventList = LocationEvent::with('event')
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } else {
            return redirect()->back()->with('error', 'You do not have permission to access this page.');
        }

        // Get menus based on location and event
        $menus = Menu::with('subMenus')
            ->where('location_id', $dailyMenu->location_id)
            ->where('event_id', $dailyMenu->event_id)
            ->orderByRaw("
            CASE
                WHEN FIELD(name,
                    'Starters',
                    'Refresher',
                    'Vegetable',
                    'Dal',
                    'Roti',
                    'Rice',
                    'Accompaniments',
                    'Dessert'
                ) = 0 THEN 999
                ELSE FIELD(name,
                    'Starters',
                    'Refresher',
                    'Vegetable',
                    'Dal',
                    'Roti',
                    'Rice',
                    'Accompaniments',
                    'Dessert'
                )
            END
        ")
            ->get();

        $selectedSubmenus = [];
        foreach ($dailyMenu->items as $item) {
            $selectedSubmenus[$item->menu_id][] = $item->submenu_id;
        }

        return view('admin.todaymenu.edit', compact(
            'dailyMenu',
            'menus',
            'selectedSubmenus',
            'locationId',
            'eventList'
        ));
    }
    public function updateTodayMenu(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'event_id' => 'required|exists:event_masters,id',
            'menu_date' => [
                'required',
                'date',
                Rule::unique('daily_menus')
                    ->where(function ($query) use ($request) {
                        return $query->where('event_id', $request->event_id)->where('location_id', $request->location_id);
                    })
                    ->ignore($id),
            ],

            'submenu_id' => 'required|array',
            'submenu_id.*' => 'array',
            'submenu_id.*.*' => 'exists:sub_menus,id',
            'special_flag' => 'required|in:0,1',
            'action' => 'required|in:1,2',


        ], [
            'menu_date.unique' => 'Menu already exists for the selected location and date.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $dayStatus = DayStatus::where('date', $request->menu_date)->where('location_id', $request->location_id)->first();

        if (!$dayStatus) {
            return back()->withErrors([
                'menu_date' => 'Day status not found.'
            ]);
        }

        $dailyMenu = DailyMenu::findOrFail($id);

        $dailyMenu->update([
            'calendar_id' => $dayStatus->id,
            'location_id' => $request->location_id,
            'menu_date' => $request->menu_date,
            'remarks' => $request->remarks,
            'special_flag' => $request->special_flag,
            'status' => $request->action,
        ]);

        // Delete old items
        $dailyMenu->items()->delete();

        // Insert new items
        foreach ($request->submenu_id as $menuId => $submenus) {

            foreach ($submenus as $submenuId) {

                $dailyMenu->items()->create([
                    'menu_id' => $menuId,
                    'submenu_id' => $submenuId,
                    'quantity' => 1
                ]);
            }
        }

        return redirect()
            ->route('today-menu.index')
            ->with('success', 'Menu updated successfully.');
    }

    public function destroyTodayMenu($id)
    {
        try {
            $user = Auth::user();

            // Find the daily menu
            $dailyMenu = DailyMenu::with('items')->findOrFail($id);

            // Check permissions based on user role
            if ($user->role == 'Canteen Administrator') {
                // Only allow deletion if the menu belongs to the user's location
                if ($dailyMenu->location_id != $user->location_id) {
                    return redirect()
                        ->back()
                        ->with('error', 'You do not have permission to delete this menu.');
                }
            } elseif ($user->role == 'Super Admin') {
                // Super Admin can delete any menu
                // No additional check needed
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'You do not have permission to delete this menu.');
            }

            // Delete related items first
            $dailyMenu->items()->delete();

            // Delete the daily menu
            $dailyMenu->delete();

            return redirect()
                ->back()
                ->with('success', 'Today\'s menu deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting menu: ' . $e->getMessage());
        }
    }

    public function getMenusByEvent(Request $request)
    {
        $user = Auth::user();
        $eventId = $request->event_id;
        $locationId = $request->location_id ?? $user->location_id;

        // Fetch menus based on event and location
        $menus = Menu::with('subMenus')
            ->where('location_id', $locationId)
            ->where('event_id', $eventId)
            ->orderByRaw("
        CASE
            WHEN FIELD(name,
                'Starters',
                'Refresher',
                'Vegetable',
                'Dal',
                'Roti',
                'Rice',
                'Accompaniments',
                'Dessert'
            ) = 0 THEN 999
            ELSE FIELD(name,
                'Starters',
                'Refresher',
                'Vegetable',
                'Dal',
                'Rice',
                'Roti',
                'Accompaniments',
                'Dessert'
            )
        END
    ")
            ->get();

        if ($menus->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No menus found for this event.'
            ]);
        }

        // Render the table HTML
        $html = view('admin.todaymenu.partial.menu_table', compact('menus'))->render();

        return response()->json([
            'status' => true,
            'html' => $html
        ]);
    }
}
