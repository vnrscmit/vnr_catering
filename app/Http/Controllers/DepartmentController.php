<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use DataTables;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\DepartmentLocation;
use App\Models\Location;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    // public function index()
    // {
    //     $departments = Department::orderBy('name', 'ASC')->paginate(10);
    //     return view('admin.departments.index', compact('departments'));
    // }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = Department::orderBy('name', 'ASC')->get();
            foreach ($data  as $datas) {
                $datas->locationcount = DepartmentLocation::where('department_id', $datas->id)->count();
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('locationlink', function ($row) {
                    return '<a href="' . route('locations.link', $row->id) . '" 
                class="fw-bold text-primary">
                ' . $row->locationcount . '
            </a>';
                })

                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-success"><i class="fa fa-check"></i> Active</span>'
                        : '<span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d-m-Y');
                })

                ->addColumn('action', function ($row) {
                    return '
                    <a href="' . route('departments.show', $row->id) . '" class="btn btn-info btn-sm" title="View">
                        <i class="fa fa-eye"></i>
                    </a>

                    <a href="' . route('departments.edit', $row->id) . '" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fa fa-edit"></i>
                    </a>

                    <button type="button" class="btn btn-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal"
                        data-id="' . $row->id . '"
                        title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>';
                })

                ->rawColumns(['locationlink', 'status', 'action'])
                ->make(true);
        }

        return view('admin.departments.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $locations = Location::where('status', 1)->get();
        $department = Department::where('status', 1)->orderBy('name', 'asc')->get();
        return view('admin.departments.create', compact('locations', 'department'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id'   => 'nullable|array',
            'location_id.*' => 'exists:locations,id',
            'name'          => 'required|string|max:255',
            'short_code'    => 'required|string|max:50',
            'status'        => 'required',
        ]);

        $department = Department::create([
            'name'        => $request->name,
            'short_code'  => $request->short_code,
            'status'      => $request->status,
        ]);

        if (!empty($request->location_id)) {
            foreach ($request->location_id as $locationId) {
                DepartmentLocation::firstOrCreate([
                    'department_id' => $department->id,
                    'location_id'   => $locationId,
                ], [
                    'status' => 1,
                ]);
            }
        }

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return view('admin.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:departments,name,' . $department->id . '|max:255',
            'short_code' => 'required|string|unique:departments,short_code,' . $department->id . '|max:50',
            'status' => 'sometimes|boolean',
        ]);

        $validated['status'] = $request->has('status') ? 1 : 0;

        $department->update($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Department deleted successfully!');
    }

    public function getDepartments($locationId)
    {
        $departments = DepartmentLocation::select(
            'department_locations.id',
            'department_locations.department_id',
            'department_locations.location_id',
            'departments.name as department_name',
            'locations.name as location_name'
        )
            ->join('departments', 'departments.id', '=', 'department_locations.department_id')
            ->join('locations', 'locations.id', '=', 'department_locations.location_id')
            ->where('department_locations.location_id', $locationId)
            ->get();

        return response()->json($departments);
    }
}
