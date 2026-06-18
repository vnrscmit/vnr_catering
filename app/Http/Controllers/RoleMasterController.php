<?php

namespace App\Http\Controllers;

use App\Models\RoleMaster;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;

class RoleMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
        
    }
    public function index()
    {
        $roles = RoleMaster::latest()->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'short_code' => 'required|string|unique:roles,short_code|max:50',
            'status' => 'required',
        ]);

        RoleMaster::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(RoleMaster $role)
    {
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RoleMaster $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RoleMaster $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id . '|max:255',
            'short_code' => 'required|string|unique:roles,short_code,' . $role->id . '|max:50',
            'status' => 'sometimes|boolean',
        ]);

        $validated['status'] = $request->has('status') ? 1 : 0;

        $role->update($validated);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleMaster $role)
    {
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully!');
    }
}
