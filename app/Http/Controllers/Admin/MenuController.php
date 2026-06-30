<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

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
}
