<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyParameter;
use App\Models\DayStatus;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\CompanyParameterLog;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class CompanyParameterController extends Controller
{
    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (in_array($user->role, ['Member', 'Non Member', 'Canteen President'])) {
            abort(403, 'Canteen Setting is not available for this role.');
        }

        if ($request->ajax()) {

            $query = CompanyParameter::with('location');

            if ($user->role == 'Canteen Incharge') {
                $query->where('location_id', $user->location_id);
            }

            $data = $query->get()
                ->sortBy(function ($item) {
                    return $item->location->name ?? '';
                })
                ->values();

            return DataTables::of($data)

                ->addIndexColumn()

                ->addColumn('location', function ($row) {
                    return $row->location->name ?? '-';
                })

                ->addColumn('member_rate', function ($row) {
                    return number_format($row->member_rate, 2);
                })

                ->addColumn('guest_rate', function ($row) {
                    return number_format($row->guest_rate, 2);
                })

                ->addColumn('non_member_rate', function ($row) {
                    return number_format($row->non_member_rate, 2);
                })

                ->addColumn('attendance_out_time', function ($row) {
                    return Carbon::parse($row->attendance_out_time)->format('h:i A');
                })

                ->addColumn('lunch_out_time', function ($row) {
                    return Carbon::parse($row->lunch_out_time)->format('h:i A');
                })

                ->addColumn('active_till_date', function ($row) {
                    return $row->active_till_date
                        ? \Carbon\Carbon::parse($row->active_till_date)->format('d-m-Y')
                        : '';
                })

                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-primary"><i class="fa fa-check"></i> Active</span>'
                        : '<span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>';
                })

                ->addColumn('action', function ($row) {

                    return '
                    <a href="' . route('company-parameters.edit', $row->id) . '" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fa fa-edit"></i>
                    </a>

                    <a href="' . route('company-parameters.show', $row->id) . '" class="btn btn-info btn-sm" title="View">
                        <i class="fa fa-eye"></i>
                    </a>
                ';
                })

                ->rawColumns(['status', 'action'])

                ->make(true);
        }

        return view('admin.companysetting.index');
    }

    public function create()
    {
        $user = Auth::user();

        if (in_array($user->role, ['Member', 'Non Member', 'Canteen President'])) {
            abort(403, 'Canteen Setting is not available for this role.');
        }

        if ($user->role == 'Canteen Incharge') {
            $locations = Location::where('status', 1)
                ->where('id', $user->location_id)
                ->orderBy('name')
                ->get();
        } else {
            $locations = Location::where('status', 1)
                ->orderBy('name')
                ->get();
        }

        return view('admin.companysetting.create', compact('locations'));
    }

    public function getByLocation($locationId)
    {
        $parameter = CompanyParameter::where('location_id', $locationId)->where('status', 1)->first();


        return response()->json([
            'status' => true,
            'data'   => $parameter
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'location_id'          => 'required|exists:locations,id',
            'member_rate'          => 'required|numeric|min:0',
            'guest_rate'           => 'required|numeric|min:0',
            'non_member_rate'      => 'required|numeric|min:0',
            'attendance_out_time'  => 'required',
            'lunch_out_time'       => 'required',
            'max_day_show'         => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $lastDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
            $candarId = DayStatus::where('date', $lastDate)->value('id');
            // Previous Active Record Inactive
            CompanyParameter::where('location_id', $request->location_id)
                ->where('status', 1)
                ->update([
                    'status' => 0,
                    'active_till_calendar_id' => $candarId,
                    'inactive_user_id' => $user->id,
                    'active_till_date' => Carbon::today()->format('Y-m-d')
                ]);

            // New Record
            CompanyParameter::create([
                'location_id'         => $request->location_id,
                'member_rate'         => $request->member_rate,
                'guest_rate'          => $request->guest_rate,
                'non_member_rate'     => $request->non_member_rate,
                'attendance_out_time' => $request->attendance_out_time,
                'lunch_out_time'      => $request->lunch_out_time,
                'max_day_show'        => $request->max_day_show,
                'status'              => 1,
                'active_till_date'    => null,
            ]);



            DB::commit();

            return redirect()
                ->route('company-parameters.index')
                ->with('success', 'Company Parameter saved successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
    public function edit(CompanyParameter $companyParameter)
    {
        $locations = Location::where('status', 1)->orderBy('name')->get();

        return view('admin.company-parameters.edit', compact('companyParameter', 'locations'));
    }

    public function update(Request $request, CompanyParameter $companyParameter)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id|unique:company_parameters,location_id,' . $companyParameter->id,
            'member_rate' => 'required|numeric|min:0',
            'guest_rate' => 'required|numeric|min:0',
            'non_member_rate' => 'required|numeric|min:0',
            'attendance_out_time' => 'required',
            'lunch_out_time' => 'required',
            'max_day_show' => 'required|integer|min:1',
            'status' => 'required'
        ]);

        $companyParameter->update($request->all());

        return redirect()->route('admin.company-parameters.index')
            ->with('success', 'Company Parameter updated successfully.');
    }

    public function destroy(CompanyParameter $companyParameter)
    {
        $companyParameter->delete();

        return redirect()->route('admin.company-parameters.index')
            ->with('success', 'Company Parameter deleted successfully.');
    }
}
