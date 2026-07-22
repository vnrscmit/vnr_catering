<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\DayStatus;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\DailyMenu;
use App\Models\Location;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
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

        if ($user->role == 'Canteen Incharge') {
            $locationList = Location::where('status', 1)->where('id',  $user->location_id)->get();
        } elseif ($user->role == 'Super Admin') {
            $locationList = Location::where('status', 1)->get();
        } else {
            return redirect()->back()->with('error', 'You do not have permission to access this page.');
        }

        if ($request->ajax()) {

            $query = Menu::with('subMenus', 'location')
                ->orderBy('name', 'ASC');

            if ($user->role == 'Canteen Incharge') {
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
                    $submenus = $row->subMenus->isNotEmpty()
                        ? $row->subMenus->pluck('name')->implode(', ')
                        : ' ';

                    // Add the "+" button after submenus
                    return $submenus . ' 
                    <a href="' . route('admin.submenus.create', $row->id) . '" 
                       class="">
                        Add New
                    </a>';
                })
                ->addColumn('location', function ($row) {
                    return $row->location->name ?? '';
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
            data-name="' . e($row->name) . '"
            data-status="' . $row->status . '">
            <i class="fa fa-edit"></i>
        </button>';
                })

                ->rawColumns(['submenus', 'status', 'action', 'location'])
                ->make(true);
        }

        return view('admin.menu.index', compact('locationList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:menus,name',
            'location_id' => 'required|exists:locations,id',
            'status' => 'required'
        ]);

        Menu::create([
            'location_id' => $request->location_id,
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
                'items.submenu'
            ]);

            // Role Wise Filter
            if ($user->role == 'Canteen Incharge') {
                $query->where('location_id', $user->location_id);
            }

            $data = $query->orderBy('menu_date', 'desc')->get();

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('location', function ($row) {
                    return $row->location->name ?? '-';
                })

                ->editColumn('menu_date', function ($row) {
                    return Carbon::parse($row->menu_date)->format('d-m-Y');
                })

                ->addColumn('menu_items', function ($row) {

                    $submenus = [];

                    foreach ($row->items as $item) {
                        if ($item->submenu) {
                            $submenus[] = $item->submenu->name;
                        }
                    }

                    return implode(', ', $submenus);
                })

                ->addColumn('action', function ($row) {

                    // Past date => No Edit/Delete
                    if (Carbon::parse($row->menu_date)->lt(Carbon::today())) {
                        return '';
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

                ->rawColumns(['action'])
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
        if ($user->role == 'Canteen Incharge') {
            $locations = Location::where('status', 1)
                ->where('id', $user->location_id)
                ->orderBy('name')
                ->get();
        } else {
            $locations = Location::where('status', 1)
                ->orderBy('name')
                ->get();
        }

        $menus = Menu::with('subMenus')
            ->orderBy('name', 'ASC')
            ->get();

        return view('admin.todaymenu.create', compact('menus', 'locations'));
    }
    public function storeTodayMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_date' => 'required|date',
            'submenu_id' => 'required|array',
            'submenu_id.*' => 'array',
            'submenu_id.*.*' => 'exists:sub_menus,id',
            'location_id' => 'required|exists:locations,id',
            'special_flag' => 'required|in:0,1',
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

        $checkExistingMenu = DailyMenu::where('menu_date', $request->menu_date)->where('location_id', $request->location_id)->first();

        if ($checkExistingMenu) {
            return back()->withErrors([
                'menu_date' => 'Daily Menu already exists for the selected date.'
            ]);
        }

        $dailyMenu = DailyMenu::create([
            'calendar_id' => $dayStatus->id,
            'location_id' => $request->location_id,
            'special_flag' => $request->special_flag,
            'menu_date' => $request->menu_date,
            'remarks' => $request->remarks,
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

        if ($user->role == 'Canteen Incharge') {
            $locations = Location::where('status', 1)
                ->where('id', $user->location_id)
                ->orderBy('name')
                ->get();
        } else {
            $locations = Location::where('status', 1)
                ->orderBy('name')
                ->get();
        }

        $menus = Menu::with('subMenus')
            ->orderBy('name')
            ->get();

        $selectedSubmenus = [];

        foreach ($dailyMenu->items as $item) {
            $selectedSubmenus[$item->menu_id][] = $item->submenu_id;
        }

        return view('admin.todaymenu.edit', compact(
            'dailyMenu',
            'menus',
            'selectedSubmenus',
            'locations'
        ));
    }

    public function updateTodayMenu(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'menu_date' => [
                'required',
                'date',
                Rule::unique('daily_menus')
                    ->where(function ($query) use ($request) {
                        return $query->where('location_id', $request->location_id);
                    })
                    ->ignore($id),
            ],

            'submenu_id' => 'required|array',
            'submenu_id.*' => 'array',
            'submenu_id.*.*' => 'exists:sub_menus,id',
            'special_flag' => 'required|in:0,1',

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
}
