<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\SubMenuRequest;
use App\Models\Menu;
use App\Models\SubMenu;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use Illuminate\Http\Request;

class SubMenuController extends Controller
{


    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    public function create($menuId)
    {
        $menu = Menu::with('subMenus')->where('id', $menuId)->first();
        return view('admin.submenu.create', compact('menu'));
    }
    public function store(SubMenuRequest $request)
    {
        // Get all submenu names from the request
        $submenuNames = $request->submenu_name ?? [];


        // Check if submenu names exist
        if (empty($submenuNames)) {
            return back()->with('error', 'Please add at least one submenu!');
        }

        // Filter out empty values (if any)
        $submenuNames = array_filter($submenuNames, function ($name) {
            return !empty(trim($name));
        });

        // Check if any valid submenu names exist after filtering
        if (empty($submenuNames)) {
            return back()->with('error', 'Please enter valid submenu names!');
        }

        // Get menu_id from request
        $menuId = $request->menu_id;

        if (!$menuId) {
            return back()->with('error', 'Menu ID is required!');
        }

        // Create multiple submenus
        $createdCount = 0;
        foreach ($submenuNames as $name) {

            $checkExist = SubMenu::where('name', $name)->first();
            if ($checkExist) {
                continue;
            } else {
                SubMenu::create([
                    'menu_id' => $menuId,
                    'name' => trim($name),
                    'status' => 1,
                ]);
                $createdCount++;
            }
        }

        return redirect()
            ->route('admin.menus.index')
            ->with('success', "{$createdCount} submenu created successfully!");
    }
    public function update(SubMenuRequest $request, $id): RedirectResponse
    {
        $submenu = SubMenu::findOrFail($id);
        $submenu->update($request->validated());

        return back()->with('success', 'Submenu updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $submenu = SubMenu::findOrFail($id);
        $submenu->delete();

        return back()->with('success', 'Submenu deleted successfully!');
    }
}
