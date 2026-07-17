<?php

namespace App\Http\Controllers;

use App\Models\HolidayList;
use App\Models\Location;
use App\Models\DayStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;


class HolidayListController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {

            $holidays = HolidayList::with(['location', 'dayStatus']);

            return DataTables::of($holidays)
                ->addIndexColumn()

                ->addColumn('type', function ($row) {
                    return $row->type ?? '';
                })

                ->addColumn('location', function ($row) {
                    return $row->location->name ?? '';
                })

                ->addColumn('holiday_date', function ($row) {
                    return optional($row->dayStatus)->date
                        ? \Carbon\Carbon::parse($row->dayStatus->date)->format('d-m-Y')
                        : '';
                })

                ->addColumn('remarks', function ($row) {
                    return $row->remarks ?? '';
                })

                ->editColumn('status', function ($row) {

                    if ($row->status == 1) {
                        return '<span class="badge bg-success">Active</span>';
                    }

                    return '<span class="badge bg-danger">Inactive</span>';
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

                ->rawColumns(['status', 'action'])
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

    public function store(Request $request)
    {
       
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'calendar_id' => 'required|exists:day_statuses,id',
            'remarks' => 'nullable|string',
        ]);

        HolidayList::create([
            'location_id' => $request->location_id,
            'calendar_id' => $request->calendar_id,
            'remarks' => $request->remarks,
            'status' => 1,
        ]);

        return redirect()->route('holiday-settings.index')
            ->with('success', 'Holiday added successfully.');
    }

    public function edit($id)
    {
        $holiday = HolidayList::findOrFail($id);

        $locations = Location::orderBy('name')->get();

        $calendars = DayStatus::orderBy('date')->get();

        return view('admin.holiday.edit', compact(
            'holiday',
            'locations',
            'calendars'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'calendar_id' => 'required|exists:day_statuses,id',
            'remarks' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $holiday = HolidayList::findOrFail($id);

        $holiday->update($request->only([
            'location_id',
            'calendar_id',
            'remarks',
            'status',
        ]));

        return redirect()->route('holiday-settings.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy($id)
    {
        HolidayList::findOrFail($id)->delete();

        return redirect()->route('holiday-settings.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}
