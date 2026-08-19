<?php

namespace App\Http\Controllers;

use App\Models\DepartmentLocation;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\Department;
use App\Models\Feedback;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

            $feedbacks = Feedback::with(['user', 'location']);

            return DataTables::of($feedbacks)
                ->addIndexColumn()

                ->addColumn('user_name', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })

                ->addColumn('location_name', function ($row) {
                    return $row->location ? $row->location->name : 'N/A';
                })

                ->editColumn('subject', function ($row) {
                    return $row->subject ?? 'N/A';
                })

                ->editColumn('description', function ($row) {
                    return $row->description ?? 'N/A';
                })

                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge bg-primary">Active</span>';
                    }

                    return '<span class="badge bg-danger">Inactive</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('d-m-Y h:i A')
                        : 'N/A';
                })

                ->addColumn('action', function ($row) {

                    $edit = route('feedback.edit', $row->id);
                    $delete = route('feedback.destroy', $row->id);

                    return '
                    <a href="' . $edit . '" class="btn btn-warning btn-sm">
                        <i class="fa fa-edit"></i>
                    </a>

                    <button type="button"
                            class="btn btn-danger btn-sm deleteFeedback"
                            data-id="' . $row->id . '">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('feedback.index');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {


        $locations = Location::where('status', 1)->get();
        $organizations = Organization::where('status', 1)->get();
        return view('admin.locations.create', compact('locations', 'organizations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $authUser = Auth::user();

        // Check if user has permission
        if ($authUser->role !== 'Super Admin') {
            return redirect()->back()->with('error', 'You do not have permission to delete bills.');
        }


        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|unique:locations,name|max:255',
            'short_code' => 'required|string|unique:locations,short_code|max:4|min:2',
            'status' => 'required',
        ]);

        $organizationCountAllowed = Organization::where('id', $request->organization_id)->value('max_location_allowed');
        $locationCount = Location::where('organization_id', $request->organization_id)->where('status', 1)->count();

        if ($organizationCountAllowed <= $locationCount) {
            return redirect()->route('locations.index')
                ->with('error', 'You have reached the maximum location limit. You are allowed to create only ' . $organizationCountAllowed . ' location under this organization. Currently, ' . $locationCount . ' location are already active.');
        }

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
     * Show the form for editing the specified location.
     */
    public function edit($id)
    {
        $location = Location::findOrFail($id);
        $organizations = Organization::where('status', 1)->get();

        return view('admin.locations.edit', compact('location', 'organizations'));
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'short_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations', 'short_code')->ignore($location->id),
            ],
            'status' => 'required|in:0,1',
        ]);

        $location->update([
            'organization_id' => $request->organization_id,
            'name' => $request->name,
            'short_code' => $request->short_code,
            'status' => $request->status,
        ]);

        return redirect()->route('locations.index')
            ->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);
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
