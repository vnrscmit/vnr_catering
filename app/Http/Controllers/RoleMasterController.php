<?php

namespace App\Http\Controllers;

use App\Models\RoleMaster;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use Yajra\DataTables\Facades\DataTables;

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
    public function index(Request $request)
    {
        if ($request->ajax()) {

         $roles = RoleMaster::orderBy('name', 'asc');

            return DataTables::of($roles)
                ->addIndexColumn()

                ->addColumn('name', function ($row) {
                    return $row->name;
                })

                ->addColumn('short_code', function ($row) {
                    return $row->short_code;
                })

                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge bg-primary">Active</span>';
                    }
                    return '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {
   
                    $edit = route('admin.roles.edit', $row->id);
                    $delete = route('admin.roles.destroy', $row->id);

                    return '

                <a href="' . $edit . '" class="btn btn-warning btn-sm">
                    <i class="fa fa-edit"></i>
                </a>

                <form action="' . $delete . '" method="POST" style="display:inline-block">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button class="btn btn-danger btn-sm"
                        onclick="return confirm(\'Are you sure?\')">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
            ';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('admin.roles.index');
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
