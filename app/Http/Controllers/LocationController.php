<?php

namespace App\Http\Controllers;

use App\Models\DepartmentLocation;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LocationController extends Controller
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

            $locations = Location::query();

            return DataTables::of($locations)
                ->addIndexColumn()

                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge bg-primary">Active</span>';
                    }

                    return '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {

                    $view = route('locations.show', $row->id);
                    $edit = route('locations.edit', $row->id);
                    $delete = route('locations.destroy', $row->id);

                    return '
                    <a href="' . $view . '" class="btn btn-info btn-sm">
                        <i class="fa fa-eye"></i>
                    </a>

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
        return view('admin.locations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $locations = Location::where('status', 1)->get();
        return view('admin.locations.create', compact('locations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|unique:locations,name|max:255',
            'short_code' => 'required|string|unique:locations,short_code|max:4|min:2',
            'status' => 'required',
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Location created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        return view('admin.locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:locations,name,' . $location->id . '|max:255',
            'short_code' => 'required|string|unique:locations,short_code,' . $location->id . '|max:50',
            'status' => 'sometimes|boolean',
        ]);

        $validated['status'] = $request->has('status') ? 1 : 0;

        $location->update($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully!');
    }

    public function link($id)
    {
        $data = Department::findOrFail($id);

        $locations = Location::where('status', 1)
            ->orderBy('name')
            ->get();

        $alreadylinkedData = DepartmentLocation::select(
            'department_locations.id',
            'department_locations.department_id',
            'department_locations.location_id',
            'departments.name as department_name',
            'locations.name as location_name'
        )
            ->join('departments', 'departments.id', '=', 'department_locations.department_id')
            ->join('locations', 'locations.id', '=', 'department_locations.location_id')
            ->where('department_locations.department_id', $id)
            ->get();

        return view('admin.locations.link', compact(
            'data',
            'locations',
            'alreadylinkedData'
        ));
    }

    public function storeLink(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'location_id'   => 'required|array',
            'location_id.*' => 'exists:locations,id',
        ]);

        DB::beginTransaction();

        try {

            foreach ($request->location_id as $locationId) {

                DepartmentLocation::updateOrCreate(
                    [
                        'department_id' => $request->department_id,
                        'location_id'   => $locationId,
                    ],
                    [
                        'status' => 1,
                    ]
                );
            }

            DB::commit();

            return redirect()
                ->route('locations.link', $request->department_id)
                ->with('success', 'Locations linked successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->route('locations.link', $request->department_id)
                ->with('error', $e->getMessage());
        }
    }

    public function getLocations($departmentId)
    {
        $user = Auth::user();

        $departments = Department::getByLocation($departmentId);


        if ($user->role == 'Super Admin') {
        } elseif ($user->role == 'Canteen Incharge' || $user->president_flag == 1) {
            $departments = $departments->where('location_id', $user->location_id);
        } else {
        }

        return response()->json($departments);
    }
}
