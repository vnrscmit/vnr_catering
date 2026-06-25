<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;

class MenuController extends Controller
{
    
    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function index()
    {
   
        $menus = Menu::with('subMenus')->get();
        return view('admin.menu.menus', compact('menus'));
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        Menu::create($request->validated());

        return back()->with('success', 'Menu created successfully!');
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
