<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStatus;
use App\Models\HolidayList;
use App\Models\Location;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\AttendanceAbsent;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     if ($request->ajax()) {

    //         $holidays = HolidayList::query()
    //             ->leftJoin('locations', 'holiday_lists.location_id', '=', 'locations.id')
    //             ->leftJoin('day_statuses', 'holiday_lists.calendar_id', '=', 'day_statuses.id')
    //             ->select(
    //                 'holiday_lists.*',
    //                 'locations.name as location_name',
    //                 'day_statuses.date as holiday_date'
    //             );

    //         return DataTables::of($holidays)
    //             ->addIndexColumn()

    //             ->addColumn('location_name', function ($row) {
    //                 return $row->location->name ?? '-';
    //             })

    //             ->addColumn('type', function ($row) {
    //                 return $row->type ?? '-';
    //             })

    //             ->addColumn('holiday_name', function ($row) {
    //                 return $row->holiday_name ?? '-';
    //             })

    //             ->addColumn('holiday_date', function ($row) {
    //                 return $row->holiday_date
    //                     ? \Carbon\Carbon::parse($row->holiday_date)->format('d-m-Y')
    //                     : '-';
    //             })
    //             ->addColumn('location_name', function ($row) {
    //                 return $row->location_name ?? '-';
    //             })

    //             ->addColumn('applicable_for', function ($row) {
    //                 return $row->applicable_for ?? '-';
    //             })

    //             ->editColumn('status', function ($row) {

    //                 return $row->status == 1
    //                     ? '<span class="badge bg-primary">Active</span>'
    //                     : '<span class="badge bg-danger">Inactive</span>';
    //             })

    //             ->addColumn('action', function ($row) {

    //                 // Hide cancel button for past holidays
    //                 if (Carbon::parse($row->holiday_date)->lt(Carbon::today())) {
    //                     return '<span class="badge bg-danger">Not Allowed</span>';
    //                 }

    //                 $delete = route('holiday-settings.destroy', $row->id);

    //                 return '
    //                 <form action="' . $delete . '" method="POST" style="display:inline-block">
    //                     ' . csrf_field() . '
    //                     ' . method_field('DELETE') . '
    //                    <button type="submit"
    //     class="btn btn-warning btn-sm"
    //     onclick="return confirm(\'Are you sure you want to cancel this holiday?\')">
    //     <i class="fa fa-ban"></i> Cancel
    // </button>
    //                 </form>
    //             ';
    //             })

    //             ->rawColumns(['status', 'action', 'location_name', 'type', 'holiday_name'])
    //             ->make(true);
    //     }

    //     return view('admin.holiday.index');
    // }


    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {
            $holidays = DB::table('locations')
                ->Join('holiday_lists', 'locations.id', '=', 'holiday_lists.location_id')
                ->select(
                    'locations.id',
                    'locations.name as location_name',
                    'year',
                    DB::raw("SUM(CASE WHEN holiday_lists.type = 'Holiday' THEN 1 ELSE 0 END) as holiday_count"),
                    DB::raw("SUM(CASE WHEN holiday_lists.type = 'Week Off' THEN 1 ELSE 0 END) as week_off_count"),
                    DB::raw("SUM(CASE WHEN holiday_lists.type = 'Special Day' THEN 1 ELSE 0 END) as special_day_count")
                )
                ->groupBy('locations.id', 'locations.name', 'holiday_lists.year');


            if ($user->role == 'Canteen Incharge') {
                $holidays->where('location_id',  $user->location_id)->get();
            } elseif ($user->role == 'Super Admin') {
            }

            return DataTables::of($holidays)
                ->addIndexColumn()

                ->addColumn('year', function ($row) {
                    return $row->year;
                })

                ->addColumn('location_name', function ($row) {
                    return $row->location_name;
                })

                ->addColumn('holiday_count', function ($row) {
                    return '<a href="' . route('holiday.details', [
                        'location' => $row->id,
                        'year'     => $row->year,
                        'type'     => 'Holiday'
                    ]) . '" class="fw-bold text-info">
        ' . $row->holiday_count . '
    </a>';
                })

                ->addColumn('week_off_count', function ($row) {
                    return '<a href="' . route('holiday.details', [
                        'location' => $row->id,
                        'year'     => $row->year,
                        'type'     => 'Week Off'
                    ]) . '" class="fw-bold text-info">
        ' . $row->week_off_count . '
    </a>';
                })

                ->addColumn('special_day_count', function ($row) {
                    return '<a href="' . route('holiday.details', [
                        'location' => $row->id,
                        'year'     => $row->year,
                        'type'     => 'Special Day'
                    ]) . '" class="fw-bold text-info">
        ' . $row->special_day_count . '
    </a>';
                })
                ->rawColumns([
                    'holiday_count',
                    'week_off_count',
                    'special_day_count'
                ])
                ->make(true);
        }

        return view('admin.holiday.index');
    }

    public function details(Request $request, $location, $year, $type)
    {
        if ($request->ajax()) {

            $holidays = HolidayList::query()
                ->leftJoin('locations', 'holiday_lists.location_id', '=', 'locations.id')
                ->leftJoin('day_statuses', 'holiday_lists.calendar_id', '=', 'day_statuses.id')
                ->select(
                    'holiday_lists.*',
                    'locations.name as location_name',
                    'day_statuses.date as holiday_date',
                    'day_statuses.day_name as day_name',
                )
                ->where('holiday_lists.location_id', $location)
                ->where('holiday_lists.year', $year)
                ->whereDate('day_statuses.date', '>=', now()->toDateString())
                ->where('holiday_lists.type', urldecode($type));

            return DataTables::of($holidays)
                ->addIndexColumn()

                ->addColumn('location_name', function ($row) {
                    return $row->location_name ?? '-';
                })

                ->addColumn('type', function ($row) {
                    return $row->type ?? '-';
                })

                ->addColumn('holiday_name', function ($row) {
                    return $row->remarks ?? ' ';
                })

                ->addColumn('day_name', function ($row) {
                    return $row->day_name ?? ' ';
                })

                ->addColumn('holiday_date', function ($row) {
                    return $row->holiday_date
                        ? \Carbon\Carbon::parse($row->holiday_date)->format('d-m-Y')
                        : '-';
                })

                ->addColumn('applicable_for', function ($row) {
                    return $row->applicable_for ?? '-';
                })

                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {

                    // Hide cancel button for past holidays
                    if (Carbon::parse($row->holiday_date)->lt(Carbon::today())) {
                        return '<span class="badge bg-danger">Not Allowed</span>';
                    }

                    $delete = route('holiday-settings.destroy', $row->id);

                    return '
                                <form action="' . $delete . '" method="POST" style="display:inline-block">
                                    ' . csrf_field() . '
                                    ' . method_field('DELETE') . '
                                   <button type="submit"
                    class="btn btn-warning btn-sm"
                    onclick="return confirm(\'Are you sure you want to cancel this holiday?\')">
                    <i class="fa fa-ban"></i> Cancel
                </button>
                                </form>
                            ';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $locationName = Location::where('id', $location)->value('name');
        return view('admin.holiday.details', compact('location', 'year', 'type', 'locationName'));
    }

    public function create()
    {
        $locations = Location::orderBy('name')->get();

        $calendars = DayStatus::orderBy('date')->get();

        return view('admin.holiday.create', compact('locations', 'calendars'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'year' => 'required',
            'location_id' => 'required|exists:locations,id',
            'remarks'     => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | Holiday Dates
        |--------------------------------------------------------------------------
        */
            if (!empty($request->holiday_dates)) {

                foreach ($request->holiday_dates as $key => $date) {

                    $calendar = DayStatus::whereDate('date', $date)
                        ->where('location_id', $request->location_id)
                        ->where('lock_flag', 0)
                        ->first();

                    if (!$calendar) {
                        continue;
                    }

                    HolidayList::firstOrCreate(
                        [
                            'location_id' => $request->location_id,
                            'calendar_id' => $calendar->id,
                            'type' => 'Holiday',
                            'year' => $request->year,
                        ],
                        [
                            'date' => $calendar->date,
                            'holiday_name' => null,
                            'applicable_for' => null,
                            'remarks' => $request->holiday_remarks[$key] ?? null,
                            'status' => 1,
                        ]
                    );

                    $calendar->update([
                        'open_flag'    => 0,
                        'holiday_flag' => 1,
                    ]);

                    $allUsers = User::where('location_id',  $request->location_id)->where('status', 1)->get();

                    foreach ($allUsers as $user) {
                        AttendanceAbsent::create([
                            'calendar_id' => $calendar->id,
                            'user_id'     => $user->id,
                            'absent_flag' => 1,
                            'location_id' => $request->location_id,
                            'status'      => 1,
                        ]);

                        // Har baar log insert hoga
                        AttendanceLog::create([
                            'calendar_id' => $calendar->id,
                            'user_id'     => $user->id,
                            'absent_flag' => 1,
                            'created_by'  => auth()->id(),
                            'remarks'     => 'Attendance updated for holiday list',
                            'status'      => 1,
                            'web_app'     => 'web',
                        ]);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Weekly Off
        |--------------------------------------------------------------------------
        */
            if (!empty($request->weekly_days)) {


                foreach ($request->weekly_days as $day) {

                    $normalizedDay = ucfirst(strtolower(trim($day)));

                    $calendars = DayStatus::where('day_name', $normalizedDay)
                        ->where('location_id', $request->location_id)
                        ->where('lock_flag', 0)
                        ->get();


                    foreach ($calendars as $calendar) {

                        HolidayList::firstOrCreate(
                            [
                                'location_id' => $request->location_id,
                                'calendar_id' => $calendar->id,
                                'year' => $request->year,
                            ],
                            [
                                'date' => $calendar->date,
                                'holiday_name' => ucfirst($day),
                                'applicable_for' => null,
                                'remarks' => $request->remarks,
                                'status' => 1,
                                'type' => 'Week Off',
                            ]
                        );

                        $calendar->update([
                            'open_flag'    => 0,
                            'holiday_flag' => 1,
                        ]);


                        $allUsers = User::where('location_id',  $request->location_id)->where('status', 1)->get();

                        foreach ($allUsers as $user) {
                            AttendanceAbsent::create([
                                'calendar_id' => $calendar->id,
                                'user_id'     => $user->id,
                                'absent_flag' => 1,
                                'location_id' => $request->location_id,
                                'status'      => 1,
                            ]);

                            // Har baar log insert hoga
                            AttendanceLog::create([
                                'calendar_id' => $calendar->id,
                                'user_id'     => $user->id,
                                'absent_flag' => 1,
                                'created_by'  => auth()->id(),
                                'remarks'     => 'Attendance updated for holiday list',
                                'status'      => 1,
                                'web_app'     => 'web',
                            ]);
                        }
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Special Dates
        |--------------------------------------------------------------------------
        */
            if (!empty($request->specific_dates)) {

                foreach ($request->specific_dates as $key => $date) {

                    $calendar = DayStatus::whereDate('date', $date)
                        ->where('location_id', $request->location_id)
                        ->where('lock_flag', 0)
                        ->first();

                    if (!$calendar) {
                        continue;
                    }

                    HolidayList::firstOrCreate(
                        [
                            'location_id' => $request->location_id,
                            'calendar_id' => $calendar->id,
                            'type' => 'Special Day',
                            'year' => $request->year,
                        ],
                        [
                            'date' => $calendar->date,
                            'holiday_name' => null,
                            'applicable_for' => null,
                            'remarks' => $request->specific_remarks[$key] ?? null,
                            'status' => 1,
                        ]
                    );

                    $calendar->update([
                        'open_flag'    => 0,
                        'holiday_flag' => 1,
                    ]);

                    $allUsers = User::where('location_id',  $request->location_id)->where('status', 1)->get();

                    foreach ($allUsers as $user) {
                        AttendanceAbsent::create([
                            'calendar_id' => $calendar->id,
                            'user_id'     => $user->id,
                            'absent_flag' => 1,
                            'location_id' => $request->location_id,
                            'status'      => 1,
                        ]);

                        // Har baar log insert hoga
                        AttendanceLog::create([
                            'calendar_id' => $calendar->id,
                            'user_id'     => $user->id,
                            'absent_flag' => 1,
                            'created_by'  => auth()->id(),
                            'remarks'     => 'Attendance updated for holiday list',
                            'status'      => 1,
                            'web_app'     => 'web',
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('holiday-settings.index')
                ->with('success', 'Holiday Masters saved successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Holiday Store Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Something went wrong. ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */



    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {

            $holiday = HolidayList::findOrFail($id);

            $calendarData = DayStatus::find($holiday->calendar_id);

            if (!$calendarData) {
                DB::rollBack();

                return redirect()
                    ->route('holiday-settings.index')
                    ->with('error', 'Associated calendar record not found.');
            }

            $today = Carbon::today();

            if (\Carbon\Carbon::parse($calendarData->date)->lt($today)) {

                DB::rollBack();

                return redirect()
                    ->route('holiday-settings.index')
                    ->with('error', 'Past holidays cannot be deleted.');
            }

            // Re-open the day
            $calendarData->update([
                'open_flag' => 1,
                'holiday_flag' => 1,
            ]);

            // Delete holiday
            $holiday->delete();

            DB::commit();

            return redirect()
                ->back()
                ->with('success', 'Holiday deleted successfully.');
        } catch (ModelNotFoundException $e) {

            DB::rollBack();

            return redirect()
                ->route('holiday-settings.index')
                ->with('error', 'Holiday not found.');
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Holiday Delete Error', [
                'holiday_id' => $id,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Something went wrong while deleting the holiday.');
        }
    }
}
