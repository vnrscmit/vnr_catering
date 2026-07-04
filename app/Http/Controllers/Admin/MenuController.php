<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\DayStatus;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = Menu::with('subMenus')
                ->orderBy('name', 'ASC')
                ->get();

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
                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-primary"><i class="fa fa-check"></i> Active</span>'
                        : '<span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>';
                })

                ->rawColumns(['submenus', 'status', 'action'])
                ->make(true);
        }

        return view('admin.menu.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:menus,name',
            'status' => 'required'
        ]);

        Menu::create([
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
        if ($request->ajax()) {

            $data = DailyMenu::with(['items.menu', 'items.submenu'])
                ->orderBy('menu_date', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()

                ->editColumn('menu_date', function ($row) {
                    return date('d-m-Y', strtotime($row->menu_date));
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

                    // Today & Future => Show buttons
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

                ->rawColumns(['menu_items', 'action'])
                ->make(true);
        }

        return view('admin.toDaymenu.index');
    }

    public function createTodayMenu()
    {
        $menus = Menu::with('subMenus')
            ->orderBy('name', 'ASC')
            ->get();

        return view('admin.todaymenu.create', compact('menus'));
    }

    public function storeTodayMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_date' => 'required|date|unique:daily_menus,menu_date',
            'submenu_id' => 'required|array',
            'submenu_id.*' => 'array',
            'submenu_id.*.*' => 'exists:sub_menus,id',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator->errors())
                ->withInput();
        }

        $dayStatus = DayStatus::where('date', $request->menu_date)->first();

        if (!$dayStatus) {
            return back()->withErrors([
                'menu_date' => 'Day Status not found for selected date.'
            ]);
        }

        $checkExistingMenu = DailyMenu::where('menu_date', $request->menu_date)->first();

        if ($checkExistingMenu) {
            return back()->withErrors([
                'menu_date' => 'Daily Menu already exists for the selected date.'
            ]);
        }

        $dailyMenu = DailyMenu::create([
            'calendar_id' => $dayStatus->id,
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
        $dailyMenu = DailyMenu::with('items')->findOrFail($id);

        $menus = Menu::with('subMenus')
            ->orderBy('name')
            ->get();

        // Selected submenu ids menu-wise
        $selectedSubmenus = [];

        foreach ($dailyMenu->items as $item) {
            $selectedSubmenus[$item->menu_id][] = $item->submenu_id;
        }

        return view('admin.todaymenu.edit', compact(
            'dailyMenu',
            'menus',
            'selectedSubmenus'
        ));
    }


    public function updateTodayMenu(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'menu_date' => 'required|date|unique:daily_menus,menu_date,' . $id,
            'submenu_id' => 'required|array',
            'submenu_id.*' => 'array',
            'submenu_id.*.*' => 'exists:sub_menus,id',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $dayStatus = DayStatus::where('date', $request->menu_date)->first();

        if (!$dayStatus) {
            return back()->withErrors([
                'menu_date' => 'Day status not found.'
            ]);
        }

        $dailyMenu = DailyMenu::findOrFail($id);

        $dailyMenu->update([
            'calendar_id' => $dayStatus->id,
            'menu_date' => $request->menu_date,
            'remarks' => $request->remarks,
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
