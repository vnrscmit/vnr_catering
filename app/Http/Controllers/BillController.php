<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAbsent;
use App\Models\Bill;
use App\Models\BillDetail;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\CompanyParameter;
use App\Models\DayStatus;
use App\Models\Guest;
use App\Models\RateMaster;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;




class BillController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

public function index(Request $request)
{
    if ($request->ajax()) {

        $bills = Bill::latest();

        return DataTables::of($bills)
            ->addIndexColumn()

            ->editColumn('generate_month', function ($row) {
                return Carbon::parse($row->generate_month . '-01')->format('F Y');
            })

            ->editColumn('bill_date', function ($row) {
                return Carbon::parse($row->bill_date)->format('d-m-Y');
            })

            ->editColumn('net_monthly_expenses', function ($row) {
                return number_format($row->net_monthly_expenses, 2);
            })

            ->editColumn('per_diet_calculation', function ($row) {
                return number_format($row->per_diet_calculation, 2);
            })

            ->addColumn('status', function ($row) {

                if ($row->status == 1) {
                    return '<span class="badge badge-success">Generated</span>';
                }

                return '<span class="badge badge-danger">Cancelled</span>';
            })

            ->addColumn('action', function ($row) {

                $buttons = '';

                $buttons .= '<a href="' . route('bill-generate.show', $row->id) . '" class="btn btn-sm btn-info me-1">
                                <i class="fa fa-eye"></i>
                             </a>';

                $buttons .= '<a href="' . route('bill-generate.edit', $row->id) . '" class="btn btn-sm btn-primary me-1">
                                <i class="fa fa-edit"></i>
                             </a>';

                $buttons .= '<button type="button"
                                class="btn btn-sm btn-danger deleteBill"
                                data-id="' . $row->id . '">
                                <i class="fa fa-trash"></i>
                             </button>';

                return $buttons;
            })

            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    return view('bill.index');
}



    public function create()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $locationId = $user->location_id;

        $previousStart = Carbon::now()->subMonth()->startOfMonth();
        $previousEnd   = Carbon::now()->subMonth()->endOfMonth();

        $calendarIds = DayStatus::whereBetween('date', [$previousStart, $previousEnd])
            ->where([
                'sunday_flag' => 0,
                'holiday_flag' => 0,
                'open_flag' => 1,
                'location_id' => $locationId
            ])
            ->pluck('id');

        if ($calendarIds->isEmpty()) {
            return redirect()->back()->with('error', 'No calendar found for previous month.');
        }

        $totalMonthDays = $calendarIds->count();

        $dietCountAbsent = AttendanceAbsent::whereIn('calendar_id', $calendarIds)
            ->where('location_id', $locationId)
            ->where('absent_flag', 1)
            ->count();

        $allUserLocation = User::where('location_id', $locationId)->where('status', 1)->count();

        $totalMonthDaysMeal =  ($allUserLocation *  $totalMonthDays);

        $presentCountAll = $totalMonthDaysMeal - $dietCountAbsent;

        $presidentId = User::where('location_id', $locationId)
            ->where('president_flag', 1)
            ->value('id');

        $presidentCountAll = 0;

        if ($presidentId) {

            $presidentAbsent = AttendanceAbsent::whereIn('calendar_id', $calendarIds)
                ->where('user_id', $presidentId)
                ->where('absent_flag', 1)
                ->count();
       

            $presidentCountAll = $totalMonthDays - $presidentAbsent;
        }

        $guestCount = Guest::whereIn('calendar_id', $calendarIds)->where('location_id', $locationId)
            ->sum('guest_count');

        $individualSettlementDiet = 0;

        $totalDiets = $presentCountAll + $presidentCountAll + $guestCount;

        $netChargeableDiet = $totalDiets - ($presidentCountAll + $guestCount + $individualSettlementDiet);

        $rateMaster = RateMaster::where('location_id', $locationId)
            ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
            ->where('status', 1)
            ->first();

        if (!$rateMaster) {
            return redirect()->back()->with('error', 'Rate master not found for ' . $previousStart->format('F Y'));
        }

        $guestExpense = $guestCount * $rateMaster->guest_rate;

        $individualExpense = 0;

        return view('bill.create', [
            'generateMonth'             => $previousStart->format('Y-m'),
            'billDate'                  => now()->format('Y-m-d'),
            'totalMonthDays'            => $totalMonthDays,
            'totalDiets'                => $totalDiets,
            'individualSettlementDiet'  => $individualSettlementDiet,
            'presidentDiet'             => $presidentCountAll,
            'guestDiet'                 => $guestCount,
            'netChargeableDiet'         => $netChargeableDiet,
            'guestExpense'              => $guestExpense,
            'individualExpense'         => $individualExpense,
            'guestRate'                 => $rateMaster->guest_rate,
            'rateMaster'                => $rateMaster,
        ]);
    }

    public function store(Request $request)
    {
        //
        // Bill generation logic here
        //
    }

    public function show(Bill $bill)
    {
        //
    }

    public function edit(Bill $bill)
    {
        //
    }

    public function update(Request $request, Bill $bill)
    {
        //
    }

    public function destroy(Bill $bill)
    {
        $bill->delete();

        return back()->with('success', 'Bill deleted successfully.');
    }
}
