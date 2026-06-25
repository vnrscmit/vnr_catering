<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubMenuRequest;
use App\Models\Menu;
use App\Models\SubMenu;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;

class SubMenuController extends Controller
{


    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function store(SubMenuRequest $request): RedirectResponse
    {
        SubMenu::create($request->validated());

        return back()->with('success', 'Submenu created successfully!');
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
