<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStatus;
use App\Models\HolidayList;
use App\Models\Location;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use Illuminate\Support\Facades\DB;

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
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $holidays = HolidayList::with(['location', 'dayStatus']);

            return DataTables::of($holidays)
                ->addIndexColumn()

                ->addColumn('location_name', function ($row) {
                    return $row->location->name ?? '-';
                })

                ->addColumn('type', function ($row) {
                    return $row->type ?? '-';
                })

                ->addColumn('holiday_name', function ($row) {
                    return $row->holiday_name ?? '-';
                })

                ->addColumn('holiday_date', function ($row) {
                    return optional($row->dayStatus)->date
                        ? \Carbon\Carbon::parse($row->dayStatus->date)->format('d-m-Y')
                        : '-';
                })

                ->addColumn('location', function ($row) {
                    return $row->location->name ?? '-';
                })

                ->addColumn('applicable_for', function ($row) {
                    return $row->applicable_for ?? '-';
                })

                ->editColumn('status', function ($row) {

                    return $row->status == 1
                        ? '<span class="badge bg-primary">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {

                    $edit = route('holiday-settings.edit', $row->id);
                    $delete = route('holiday-settings.destroy', $row->id);

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

                ->rawColumns(['status', 'action', 'location_name', 'type', 'holiday_name'])
                ->make(true);
        }

        return view('admin.holiday.index');
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

                foreach ($request->holiday_dates as $date) {

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
                        ],
                        [
                            'date' => $calendar->date,
                            'holiday_name' => null,
                            'applicable_for' => null,
                            'remarks' => $request->remarks,
                            'status' => 1,
                        ]
                    );

                    $calendar->update([
                        'open_flag'    => 0,
                        'holiday_flag' => 1,
                    ]);
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
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Special Dates
        |--------------------------------------------------------------------------
        */
            if (!empty($request->specific_dates)) {

                foreach ($request->specific_dates as $date) {

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
                        ],
                        [
                            'date' => $calendar->date,
                            'holiday_name' => null,
                            'applicable_for' => null,
                            'remarks' => $request->remarks,
                            'status' => 1,
                        ]
                    );

                    $calendar->update([
                        'open_flag'    => 0,
                        'holiday_flag' => 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('holiday-settings.index')
                ->with('success', 'Holiday settings saved successfully.');
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
        //
    }
}
