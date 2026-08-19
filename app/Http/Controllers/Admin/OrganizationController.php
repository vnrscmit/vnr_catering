<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class OrganizationController extends Controller
{
    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $organizations = Organization::query();

            return DataTables::of($organizations)
                ->addIndexColumn()

                // Organization Name with Logo
                ->editColumn('organization_name', function ($row) {

                    return  $row->organization_name;
                })

                ->editColumn('logo', function ($row) {
                    if ($row->logo) {
                        return '<img src="' . asset('storage/organizations/logos/' . $row->logo) . '" 
                                 alt="' . $row->organization_name . '" 
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef;">';
                    } else {
                        return '<span style="width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center; border: 2px solid #e9ecef; font-size: 16px; color: #6c757d; font-weight: 600;">
                                ' . strtoupper(substr($row->organization_name, 0, 1)) . '
                            </span>';
                    }
                })


                // Short Name
                ->editColumn('short_name', function ($row) {
                    return  $row->short_name;
                })

                // Location (State, District, City)
                ->editColumn('location', function ($row) {
                    $location = [];
                    if ($row->city_village) $location[] = $row->city_village;
                    if ($row->district) $location[] = $row->district;
                    if ($row->state) $location[] = $row->state;
                    return implode(', ', $location);
                })

                // Pincode
                ->editColumn('pincode', function ($row) {
                    return '<span class="badge bg-secondary">' . $row->pincode . '</span>';
                })

                // Status with Badge
                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-primary"><i class="fa fa-check"></i> Active</span>'
                        : '<span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>';
                })

                // GSTIN
                ->editColumn('gstin', function ($row) {
                    if ($row->gstin) {
                        return '<span class="badge bg-warning">' . $row->gstin . '</span>';
                    }
                    return '<span class="badge bg-secondary">N/A</span>';
                })

                // Max Locations & Users
                ->editColumn('limits', function ($row) {
                    return '<span class="badge bg-primary">Locations: ' . $row->max_location_allowed . '</span> 
                            <span class="badge bg-info">Users/Location: ' . $row->max_user_per_location . '</span>';
                })

                // Created At
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d-M-Y H:i') : 'N/A';
                })

                // Action Buttons
                ->addColumn('action', function ($row) {
                    $view = route('organizations.show', $row->id);
                    $edit = route('organizations.edit', $row->id);
                    $delete = route('organizations.destroy', $row->id);

                    return '
        <div class="d-flex" style="gap: 2px;">
            <a href="' . $edit . '" class="btn btn-warning btn-sm" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
            <form action="' . $delete . '" method="POST" style="display:inline-block">
                ' . csrf_field() . '
                ' . method_field('DELETE') . '
                <button type="submit" class="btn btn-danger btn-sm" 
                        onclick="return confirm(\'Are you sure you want to delete this organization?\')" 
                        title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    ';
                })


                ->rawColumns([
                    'organization_name',
                    'short_name',
                    'location',
                    'pincode',
                    'status',
                    'gstin',
                    'limits',
                    'created_at',
                    'logo',
                    'action'
                ])
                ->make(true);
        }

        return view('admin.organizations.index');
    }

    public function create()
    {
        $states = DB::table('master_states')->where('status', 1)->get();
        return view('admin.organizations.create', compact('states'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Basic Information
            'organization_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'address' => 'required|string',

            // Location Details (storing names directly)
            'state' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'tehsil' => 'nullable|string|max:100',
            'city_village' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',

            // Configuration & Settings
            'max_location_allowed' => 'required|integer|min:1',
            'max_user_per_location' => 'required|integer|min:1',

            // Additional Information
            'gstin' => 'nullable|string|max:15|unique:organizations,gstin',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ], [
            // Custom Error Messages
            'organization_name.required' => 'Organization name is required.',
            'organization_name.max' => 'Organization name cannot exceed 255 characters.',
            'short_name.required' => 'Short name is required.',
            'short_name.max' => 'Short name cannot exceed 50 characters.',
            'address.required' => 'Address is required.',
            'state.required' => 'State is required.',
            'state.max' => 'State name cannot exceed 100 characters.',
            'district.required' => 'District is required.',
            'district.max' => 'District name cannot exceed 100 characters.',
            'tehsil.max' => 'Tehsil name cannot exceed 100 characters.',
            'city_village.required' => 'City/Village is required.',
            'city_village.max' => 'City/Village cannot exceed 100 characters.',
            'pincode.required' => 'Pincode is required.',
            'pincode.max' => 'Pincode cannot exceed 10 characters.',
            'max_location_allowed.required' => 'Maximum locations allowed is required.',
            'max_location_allowed.min' => 'Maximum locations allowed must be at least 1.',
            'max_user_per_location.required' => 'Maximum users per location is required.',
            'max_user_per_location.min' => 'Maximum users per location must be at least 1.',
            'gstin.unique' => 'This GSTIN is already registered.',
            'gstin.max' => 'GSTIN cannot exceed 15 characters.',
            'logo.image' => 'The logo must be an image file.',
            'logo.mimes' => 'Logo must be a file of type: jpeg, png, jpg, gif.',
            'logo.max' => 'Logo size must not exceed 2MB.',
            'status.required' => 'Status is required.',
        ]);

        try {
            $organization = new Organization();

            // Fill all fields directly (no mapping needed since form fields match database columns)
            $organization->organization_name = $validated['organization_name'];
            $organization->short_name = $validated['short_name'];
            $organization->address = $validated['address'];
            $organization->state = $validated['state'];
            $organization->district = $validated['district'];
            $organization->tehsil = $validated['tehsil'] ?? null;
            $organization->city_village = $validated['city_village'];
            $organization->pincode = $validated['pincode'];
            $organization->max_location_allowed = $validated['max_location_allowed'];
            $organization->max_user_per_location = $validated['max_user_per_location'];
            $organization->gstin = $validated['gstin'] ?? null;
            $organization->status = $validated['status'];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $filename = Str::random(40) . '.' . $logo->getClientOriginalExtension();
                $logo->storeAs('organizations/logos', $filename, 'public');
                $organization->logo = $filename;
            }

            $organization->save();

            return redirect()->route('organizations.index')
                ->with('success', 'Organization created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create organization: ' . $e->getMessage());
        }
    }

    public function show(Organization $organization)
    {
        return view('admin.organizations.show', compact('organization'));
    }

    public function edit($id)
    {
        $organization = Organization::findOrFail($id);
        $states = DB::table('master_states')->where('status', 1)->get();

        // Get district and tehsil names if they exist
        $districtName = $organization->district;
        $tehsilName = $organization->tehsil;

        return view('admin.organizations.edit', compact('organization', 'states', 'districtName', 'tehsilName'));
    }

    /**
     * Update the specified organization in storage.
     */
    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'address' => 'required|string',
            'state' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'tehsil' => 'nullable|string|max:100',
            'city_village' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'max_location_allowed' => 'required|integer|min:1',
            'max_user_per_location' => 'required|integer|min:1',
            'gstin' => 'nullable|string|max:15|unique:organizations,gstin,' . $id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ], [
            'organization_name.required' => 'Organization name is required.',
            'short_name.required' => 'Short name is required.',
            'state.required' => 'State is required.',
            'district.required' => 'District is required.',
            'city_village.required' => 'City/Village is required.',
            'pincode.required' => 'Pincode is required.',
            'gstin.unique' => 'This GSTIN is already registered.',
        ]);

        try {
            $organization->organization_name = $validated['organization_name'];
            $organization->short_name = $validated['short_name'];
            $organization->address = $validated['address'];
            $organization->state = $validated['state'];
            $organization->district = $validated['district'];
            $organization->tehsil = $validated['tehsil'] ?? null;
            $organization->city_village = $validated['city_village'];
            $organization->pincode = $validated['pincode'];
            $organization->max_location_allowed = $validated['max_location_allowed'];
            $organization->max_user_per_location = $validated['max_user_per_location'];
            $organization->gstin = $validated['gstin'] ?? null;
            $organization->status = $validated['status'];

            // Handle logo update
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($organization->logo) {
                    Storage::disk('public')->delete('organizations/logos/' . $organization->logo);
                }

                $logo = $request->file('logo');
                $filename = Str::random(40) . '.' . $logo->getClientOriginalExtension();
                $logo->storeAs('organizations/logos', $filename, 'public');
                $organization->logo = $filename;
            }

            $organization->save();

            return redirect()->route('organizations.index')
                ->with('success', 'Organization "' . $organization->organization_name . '" updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Organization update failed: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to update organization. Please try again.');
        }
    }

    /**
     * Remove the specified organization from storage.
     */
    public function destroy($id)
    {
        try {
            $organization = Organization::findOrFail($id);

            // Check if organization has associated locations
            $locationCount = Location::where('organization_id', $organization->id)->count();

            if ($locationCount > 0) {
                return redirect()->route('organizations.index')
                    ->with('error', 'Cannot delete "' . $organization->organization_name . '" because it has ' . $locationCount . ' associated location. Please delete all locations first.');
            }

            $name = $organization->organization_name;

            // Delete logo if exists
            if ($organization->logo) {
                Storage::disk('public')->delete('organizations/logos/' . $organization->logo);
            }

            $organization->delete();

            return redirect()->route('organizations.index')
                ->with('success', 'Organization "' . $name . '" deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('organizations.index')
                ->with('error', 'Failed to delete organization: ' . $e->getMessage());
        }
    }

    /**
     * Get districts by state ID
     */
    public function getDistricts(Request $request)
    {
        try {
            $stateId = $request->input('state_id');

            if (!$stateId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'State ID is required'
                ], 400);
            }

            $districts = DB::table('master_districts')->where('state_id', $stateId)
                ->where('status', 1) // Assuming 1 = active
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'status' => 'success',
                'districts' => $districts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch districts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tehsils by district ID
     */
    public function getTehsils(Request $request)
    {
        try {
            $districtId = $request->input('district_id');

            if (!$districtId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'District ID is required'
                ], 400);
            }

            $tehsils = DB::table('master_tehsils')->where('district_id', $districtId)
                ->where('status', 1) // Assuming 1 = active
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'status' => 'success',
                'tehsils' => $tehsils
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tehsils: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the organization logo.
     */
    public function removeLogo($id)
    {
        try {
            $organization = Organization::findOrFail($id);

            if ($organization->logo) {
                Storage::disk('public')->delete('organizations/logos/' . $organization->logo);
                $organization->logo = null;
                $organization->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Logo removed successfully!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No logo found to remove.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove logo: ' . $e->getMessage()
            ], 500);
        }
    }
}
