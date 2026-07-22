<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyParameter;
use App\Models\DayStatus;
use App\Models\Location;
use App\Models\FeastDayRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\CompanyParameterLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\RateMaster;
use Illuminate\Support\Facades\Validator;


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
            abort(403, 'Canteen Parameter is not available for this role.');
        }

        if ($request->ajax()) {

            $query = CompanyParameter::with('location');

            if ($user->role == 'Canteen Incharge') {
                $query->where('location_id', $user->location_id)->orderByDesc('id');
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

                ->addColumn('security_deposit_applicable', function ($row) {
                    return $row->security_deposit_applicable;
                })

                ->addColumn('security_deposit_amount', function ($row) {
                    return number_format($row->security_deposit_amount, 2);
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

        if (in_array($user->role, ['Member', 'Non Member'])) {
            abort(403, 'Canteen Parameter is not available for this role.');
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
        'attendance_out_time'  => 'required',
        'lunch_out_time'       => 'required',
        'canteen_start_time'   => 'required',
        'canteen_end_time'     => 'required|after:canteen_start_time',
        'max_day_show'         => 'required|integer|min:1',
        'security_deposit_applicable' => 'required|in:yes,no',
        'security_deposit_amount' => 'required_if:security_deposit_applicable,yes|nullable|numeric|min:0',
    ]);

    DB::beginTransaction();

    try {

        $lastDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');

        $calendarId = DayStatus::where('date', $lastDate)
            ->where('location_id', $request->location_id)
            ->value('id');

        // Previous Active Record Inactive
        CompanyParameter::where('location_id', $request->location_id)
            ->where('status', 1)
            ->update([
                'status'                  => 0,
                'active_till_calendar_id' => $calendarId,
                'inactive_user_id'        => $user->id,
                'active_till_date'        => Carbon::today()->format('Y-m-d'),
            ]);

        // New Record
        CompanyParameter::create([
            'location_id'          => $request->location_id,
            'attendance_out_time'  => $request->attendance_out_time,
            'lunch_out_time'       => $request->lunch_out_time,
            'canteen_start_time'   => $request->canteen_start_time,
            'canteen_end_time'     => $request->canteen_end_time,
            'max_day_show'         => $request->max_day_show,
            'security_deposit_applicable' => $request->security_deposit_applicable,
            'security_deposit_amount' => $request->security_deposit_applicable == 'yes' 
                ? $request->security_deposit_amount 
                : null,
            'status'               => 1,
            'active_till_date'     => null,
            'active_till_calendar_id' => null,
            'inactive_user_id'     => null,
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


    public function rateMasterIndex(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {

            $query = RateMaster::join('locations', 'rate_masters.location_id', '=', 'locations.id')
                ->select(
                    'rate_masters.*',
                    'locations.name as location_name'
                );

            // Role Wise Filter
            if (in_array($user->role, ['Canteen Incharge', 'Canteen President'])) {
                $query->where('rate_masters.location_id', $user->location_id);
            } elseif (!in_array($user->role, ['Admin', 'Super Admin'])) {
                return DataTables::of(collect())->make(true);
            }

            $data = $query->orderBy('rate_masters.effective_from_date', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('location', function ($row) {
                    return $row->location_name;
                })

                ->editColumn('effective_from_date', function ($row) {
                    return Carbon::parse($row->effective_from_date)->format('M-Y');
                })

                ->editColumn('member_rate', function ($row) {
                    return number_format($row->member_rate, 2);
                })

                ->editColumn('guest_rate', function ($row) {
                    return number_format($row->guest_rate, 2);
                })

                ->editColumn('non_member_rate', function ($row) {
                    return number_format($row->non_member_rate, 2);
                })

                ->editColumn('min_day_rate', function ($row) {
                    return number_format($row->min_day_rate, 2);
                })

                ->addColumn('feast_day', function ($row) {

                    $feastDays = FeastDayRate::where('rate_master_id', $row->id)
                        ->pluck('feast_date')
                        ->map(function ($date) {
                            return \Carbon\Carbon::parse($date)->format('d-m-Y');
                        })
                        ->implode('<br>');

                    $feastDays = $feastDays ?: ' ';

                    return $feastDays . '
        <a href="' . route('rate-masters.feast_day', $row->id) . '" class="">
            Add New
        </a>';
                })
                ->rawColumns(['feast_day_rates'])

                ->addColumn('status', function ($row) {

                    return $row->status
                        ? '<span class="badge bg-primary">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {

                    return '
                    <a href="' . route('rate-masters.edit', $row->id) . '" class="btn btn-warning btn-sm">
                        <i class="fa fa-edit"></i>
                    </a>

                    <button
                        class="btn btn-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal"
                        data-id="' . $row->id . '">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
                })

                ->rawColumns(['status', 'action', 'feast_day'])
                ->make(true);
        }

        return view('admin.rate.index');
    }

    // Show Add Rate Form
    public function rateMasterCreate()
    {
        $UserData = Auth::user();

        if ($UserData->role == 'Admin' || $UserData->role == 'Super Admin') {
            $locations = Location::where('status', 1)->get();
        } elseif ($UserData->role == 'Canteen President' || $UserData->role == 'Canteen Incharge') {
            $locations = Location::where('status', 1)->where('id', $UserData->location_id)->get();
        } else {
            return redirect()->back()->with('error', 'Rate Master not allowed .');
        }

        return view('admin.rate.create', compact('locations'));
    }

    // Store Rate


    public function rateMasterStore(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'location_id'      => 'required|exists:locations,id',
            'effective_month'  => 'required',
            'member_rate'      => 'required|numeric|min:0',
            'guest_rate'       => 'required|numeric|min:0',
            'non_member_rate'  => 'required|numeric|min:0',
            'min_day_rate'  => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }



        $effective_from_date = $request->effective_month . '-01';

        // Get Calendar
        $fromCalendar = DayStatus::whereDate('date', $effective_from_date)
            ->where('location_id', $request->location_id)
            ->first();

        if (!$fromCalendar) {
            return back()
                ->withInput()
                ->with('error', 'Calendar not found for Effective From Date.');
        }

        // Check Existing Rate
        // $checkExist = RateMaster::where('location_id', $request->location_id)
        //     ->where('status', 1)
        //     ->whereDate('effective_from_date', '>=', $effective_from_date)
        //     ->where(function ($query) use ($effective_from_date) {
        //         $query->whereNull('effective_to_date')
        //             ->orWhereDate('effective_to_date', '>=', $effective_from_date);
        //     })
        //     ->exists();

        // if ($checkExist) {
        //     return back()
        //         ->withInput()
        //         ->withErrors([
        //             'effective_month' => 'A rate already exists for the selected month.'
        //         ]);
        // }

        RateMaster::create([
            'location_id'                => $request->location_id,
            'effective_from_date'        => $effective_from_date,
            'effective_from_calendar_id' => $fromCalendar->id,
            'member_rate'                => $request->member_rate,
            'guest_rate'                 => $request->guest_rate,
            'non_member_rate'            => $request->non_member_rate,
            'min_day_rate'                => $request->min_day_rate,
            'created_by'                 => Auth::id(),
            'status'                     => 1,
        ]);

        DB::commit();

        return redirect()
            ->route('rate-masters.index')
            ->with('success', 'Rate Master created successfully.');
    }

    // Edit Rate
    public function rateMasterEdit($id)
    {
        //
    }

    // Update Rate
    public function rateMasterUpdate(Request $request, $id)
    {
        //
    }

    // Delete Rate
    public function rateMasterDestroy($id)
    {
        //
    }

    // Change Status
    public function rateMasterStatus($id)
    {
        //
    }

    // Get Active Rate by Date & Location
    public function getActiveRate($locationId, $date)
    {
        //
    }

    public function feastDay($rateMasterId)
    {
        $rateMaster = RateMaster::join('locations', 'rate_masters.location_id', '=', 'locations.id')
            ->select('rate_masters.*', 'locations.name as location_name')
            ->where('rate_masters.id', $rateMasterId)
            ->firstOrFail();

        $effectiveDate = Carbon::parse($rateMaster->effective_from_date);


        // Min Date = Today ya Effective Date, jo bhi baad me ho
        $minDate = Carbon::today()->greaterThan($effectiveDate)
            ? Carbon::today()
            : $effectiveDate;

        // Max Date = Effective Date wale month ka last day
        $maxDate = $effectiveDate->copy()->endOfMonth();

        return view('admin.rate.feast_day', compact(
            'rateMaster',
            'minDate',
            'maxDate'
        ));
    }




    public function feastDayStore(Request $request)
    {

        $request->validate([
            'feast_date.*'      => 'required|date',
            'member_rate.*'     => 'required|numeric|min:0',
            'non_member_rate.*' => 'required|numeric|min:0',
            'guest_rate.*'      => 'required|numeric|min:0',
            'rate_master_id' => 'required',
        ]);


        $locationId = RateMaster::where('id', $request->rate_master_id)->value('location_id');
        // Check duplicate dates first
        $duplicateDates = FeastDayRate::where('rate_master_id', $request->rate_master_id)
            ->where('location_id',  $locationId)
            ->whereIn('feast_date', $request->feast_date)
            ->pluck('feast_date')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('d-m-Y');
            })
            ->toArray();

        if (!empty($duplicateDates)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'You have selected duplicate Feast Date(s): ' . implode(', ', $duplicateDates));
        }


        DB::beginTransaction();

        try {

            foreach ($request->feast_date as $key => $date) {

                $calendar = DayStatus::where('date', $date)->where('location_id',  $locationId)->first();

                FeastDayRate::create([
                    'location_id'        => $locationId,
                    'rate_master_id'        => $request->rate_master_id,
                    'feast_day_calendar_id' => optional($calendar)->id,
                    'feast_date'            => $date,
                    'member_rate'           => $request->member_rate[$key],
                    'non_member_rate'       => $request->non_member_rate[$key],
                    'guest_rate'            => $request->guest_rate[$key],
                    'status'                => 1,
                ]);
            }

            DB::commit();

            return redirect()->route('rate-masters.index')->with('success', 'Feast Day Rates added successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
