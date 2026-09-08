<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAbsent;
use App\Models\Bill;
use App\Models\BillDetail;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\CompanyParameter;
use App\Models\DayStatus;
use App\Models\Department;
use App\Models\Guest;
use App\Models\Ledger;
use App\Models\Location;
use App\Models\LocationEvent;
use App\Models\MultipleLocation;
use App\Models\RateMaster;
use App\Models\User;
use App\Models\UserEvent;
use App\Models\UserLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

class BillController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    public function individualSettlement(Request $request)
    {
        $authUser = Auth::User();

        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill is not available for this role.');
        }

        if ($request->ajax()) {
            $bills = Bill::where('type', 'Individual')->latest();

            return DataTables::of($bills)
                ->addIndexColumn()

                // Add user name column
                ->addColumn('user_name', function ($row) {
                    return $row->user ? $row->user->first_name : 'N/A';
                })

                ->editColumn('generate_month', function ($row) {
                    return Carbon::parse($row->generate_month . '-01')->format('M-Y');
                })
                ->editColumn('bill_date', function ($row) {
                    return Carbon::parse($row->bill_date)->format('d-m-Y');
                })

                ->editColumn('total_diets', function ($row) {
                    return number_format($row->total_diets);
                })

                ->editColumn('net_chargeable_diet', function ($row) {
                    return '₹ ' . number_format($row->net_monthly_expenses, 2);
                })

                ->editColumn('per_diet_calculation', function ($row) {
                    return '₹ ' . number_format($row->per_diet_calculation, 2);
                })

                ->addColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge badge-primary">Submitted</span>';
                    } elseif ($row->status == 2) {
                        return '<span class="badge badge-warning">Pending</span>';
                    }
                })

                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<a href="' . route('bill-generate.individual.show', $row->id) . '"
        class="btn btn-sm btn-info me-1"
        title="View">
        <i class="fa fa-eye"></i>
    </a>';

                    if ($row->status == 2 || $row->status == 0) {
                        $buttons .= '<a href="' . route('bill-generate.individual.edit', $row->id) . '"
        class="btn btn-sm btn-warning me-1"
        title="Edit">
        <i class="fa fa-edit"></i>
    </a>';


                        $buttons .= '<a href="' . route('bill-generate.individual.delete', $row->id) . '"
        class="btn btn-sm btn-danger me-1"
        title="Delete">
         <i class="fa fa-trash"></i>
    </a>';
                    } else if ($row->status == 1) {
                        $buttons .= '<a href="' . route('bill-generate.individual.delete', $row->id) . '"
        class="btn btn-sm btn-danger me-1"
        title="Delete">
         <i class="fa fa-trash"></i>
    </a>';
                    }

                    return '<div class="d-flex" style="gap: 2px;">' . $buttons . '</div>';
                })->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('bill.individual.index');
    }
    public function individualCreate(Request $request)
    {

        $authUser = Auth::User();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill is not available for this role.');
        }

        $locationId = $authUser->location_id;

        $singleLinkedUserIds = User::where('location_id', $locationId)
            ->whereNotNull('start_calendar_id')
            ->where('status', 1)
            ->pluck('id');

        $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
            ->where('multiple_locations.location_id', $locationId)
            ->where('users.status', 1)
            ->pluck('multiple_locations.user_id');

        $allLinkedUserIds = $singleLinkedUserIds
            ->merge($multiLinkedUserIds)
            ->unique()
            ->values();

        $allUser = User::whereIn('id', $allLinkedUserIds)->get();

        $departments = Department::getByDepartment($locationId);

        return view('bill.individual.create', [
            'allUser' => $allUser,
            'departments' => $departments
        ]);
    }
    public function individualStore(Request $request)
    {
        $authUser = Auth::user();

        // Validate the request
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'user_id' => 'required|exists:users,id',
            'settlement_month' => 'required|date_format:Y-m',
            'settlement_date' => 'required|date',
            'charge_date' => 'required|date',
            'current_outstanding' => 'required|numeric|min:0',
            'total_diets' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'total_net_chargable' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'net_settlement_charges' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        // Handle draft vs submit (status: 1 = draft, 2 = submitted)
        $action = 2;
        $status = $action;

        // Get user details
        $user = User::find($request->user_id);
        $department = Department::find($request->department_id);

        $locationShortCode = Location::where('id', $authUser->location_id)->value('short_code');

        // Generate bill number
        $billNumber = $this->generateBillNumber($locationShortCode);

        $dayStatus = DayStatus::where('date', $request->settlement_date)->first();

        // ===== Create Bill Record =====
        $bill = Bill::create([
            'type' => 'Individual',
            'user_id' => $request->user_id,
            'charge_date' => $request->charge_date,
            'generate_date' => $request->settlement_date,
            'generate_month' => $request->settlement_month,
            'calendar_id' => $dayStatus ? $dayStatus->id : null,
            'bill_no' => $billNumber,
            'bill_date' => $request->settlement_date,
            'total_diets' => $request->total_diets,
            'individual_set_diet' => $request->total_diets,
            'president_diet' => 0,
            'guest_diet' => 0,
            'net_chargeable_diet' => $request->total_diets,
            'total_expenses' => 0,
            'guest_expenses' => 0,
            'individual_expenses' => $request->total_net_chargable,
            'net_monthly_expenses' => $request->net_settlement_charges,
            'per_diet_calculation' => $request->rate,
            'status' => $status,
            'remarks' => $request->remarks,
        ]);

        // Check if bill was created successfully
        if (!$bill || !$bill->id) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create bill. Please try again.');
        }

        // ===== Create Bill Detail Record =====
        $billDetail = BillDetail::create([
            'bill_id' => $bill->id,
            'type' => 'Individual',
            'user_id' => $request->user_id,
            'user_diets' => $request->total_diets,
            'rate_per_diet' => $request->rate,
            'bill_amount' => $request->total_net_chargable,
            'net_chargeable_amount' => $request->total_net_chargable,
            'security_amount' => $request->security_deposit,
            'settlement_amount' => $request->net_settlement_charges,
            'status' => $status,
        ]);

        // Check if bill detail was created
        if (!$billDetail || !$billDetail->id) {
            // Optional: Delete the bill if detail creation fails
            $bill->delete();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create bill details. Please try again.');
        }

        // Success message based on action
        $message = 'Settlement saved as draft successfully!';

        return redirect()->route('bill-generate.individual.edit', ['id' => $bill->id])
            ->with('success', $message);
    }

    /**
     * Show the form for editing the specified individual bill.
     */

    public function individualShow($id)
    {
        $authUser = Auth::user();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill is not available for this role.');
        }

        $locationId = $authUser->location_id;

        // Get the bill
        $bill = Bill::with(['user'])->findOrFail($id);

        // Check if bill belongs to this location
        if ($bill->user->location_id != $locationId) {
            return redirect()->back()->with('error', 'You are not authorized to edit this bill.');
        }

        // Get bill detail
        $billDetail = BillDetail::where('bill_id', $bill->id)->first();

        // Get users for dropdown
        $singleLinkedUserIds = User::where('location_id', $locationId)
            ->whereNotNull('start_calendar_id')
            ->where('status', 1)
            ->pluck('id');

        $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
            ->where('multiple_locations.location_id', $locationId)
            ->where('users.status', 1)
            ->pluck('multiple_locations.user_id');

        $allLinkedUserIds = $singleLinkedUserIds
            ->merge($multiLinkedUserIds)
            ->unique()
            ->values();

        $allUser = User::whereIn('id', $allLinkedUserIds)->get();
        $departments = Department::getByDepartment($locationId);

        return view('bill.individual.show', compact('bill', 'billDetail', 'allUser', 'departments'));
    }


    public function individualEdit($id)
    {
        $authUser = Auth::user();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill is not available for this role.');
        }

        $locationId = $authUser->location_id;

        // Get the bill
        $bill = Bill::with(['user'])->findOrFail($id);

        // Check if bill belongs to this location
        if ($bill->user->location_id != $locationId) {
            return redirect()->back()->with('error', 'You are not authorized to edit this bill.');
        }

        // Get bill detail
        $billDetail = BillDetail::where('bill_id', $bill->id)->first();

        // Get users for dropdown
        $singleLinkedUserIds = User::where('location_id', $locationId)
            ->whereNotNull('start_calendar_id')
            ->where('status', 1)
            ->pluck('id');

        $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
            ->where('multiple_locations.location_id', $locationId)
            ->where('users.status', 1)
            ->pluck('multiple_locations.user_id');

        $allLinkedUserIds = $singleLinkedUserIds
            ->merge($multiLinkedUserIds)
            ->unique()
            ->values();

        $allUser = User::whereIn('id', $allLinkedUserIds)->get();
        $departments = Department::getByDepartment($locationId);

        return view('bill.individual.edit', compact('bill', 'billDetail', 'allUser', 'departments'));
    }

    /**
     * Update the specified individual bill in storage.
     */
    public function individualUpdate(Request $request, $id)
    {
        $bill = Bill::findOrFail($id);
        $billDetail = BillDetail::where('bill_id', $bill->id)->first();

        // Validate the request
        $validated = $request->validate([
            'settlement_month' => 'required|date_format:Y-m',
            'settlement_date' => 'required|date',
            'total_diets' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'total_net_chargable' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'net_settlement_charges' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        // Get action from form
        $action = $request->input('action', 'submitted');

        // Set status: 1 = Submitted, 0 = Draft
        $status = $request->action;
        // Get day status
        $dayStatus = DayStatus::where('date', $request->settlement_date)->first();

        // ===== Update Bill Record =====
        $bill->update([
            'user_id' => $bill->user_id, // Keep existing user
            'generate_date' => $request->settlement_date,
            'generate_month' => $request->settlement_month,
            'calendar_id' => $dayStatus ? $dayStatus->id : null,
            'bill_date' => $request->settlement_date,
            'total_diets' => $request->total_diets,
            'individual_set_diet' => $request->total_diets,
            'president_diet' => 0,
            'guest_diet' => 0,
            'net_chargeable_diet' => $request->total_diets,
            'total_expenses' => 0,
            'guest_expenses' => 0,
            'individual_expenses' => $request->total_net_chargable,
            'net_monthly_expenses' => $request->net_settlement_charges,
            'per_diet_calculation' => $request->rate,
            'status' => $status, // 1 = Submitted, 0 = Draft
            'remarks' => $request->remarks,
        ]);

        // ===== Update Bill Detail Record =====
        if ($billDetail) {
            $billDetail->update([
                'type' => 'Individual',
                'user_id' => $bill->user_id,
                'user_diets' => $request->total_diets,
                'rate_per_diet' => $request->rate,
                'bill_amount' => $request->total_net_chargable,
                'net_chargeable_amount' => $request->total_net_chargable,
                'security_amount' => $request->security_deposit,
                'settlement_amount' => $request->net_settlement_charges,
                'status' => $status, // 1 = Submitted, 0 = Draft
            ]);
        }

        $message = $status == 0
            ? 'Settlement draft updated successfully!'
            : 'Settlement submitted successfully!';


        User::where('id', $bill->user_id)->where('status', 1)->update(['status' => 0]);

        return redirect()->route('bill-generate.individual')
            ->with('success', $message);
    }

    public function individualDelete($id)
    {
        $authUser = Auth::user();

        // Check if user has permission
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'You do not have permission to delete bills.');
        }

        // Find the bill
        $bill = Bill::where('id', $id)->first();

        if (!$bill) {
            return redirect()->back()->with('error', 'Bill not found.');
        }

        // Check if bill is submitted (status != draft)
        if ($bill->status == 1) {
            return redirect()->back()->with('error', 'Submitted bills cannot be deleted.');
        }

        try {
            // Delete BillDetail first (foreign key constraint)
            $billDetailDeleted = BillDetail::where('bill_id', $id)->delete();

            // Delete Bill
            $billDeleted = $bill->delete();

            if ($billDeleted) {
                return redirect()->back() // Update route name as needed
                    ->with('success', 'Bill and its details deleted successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to delete bill.');
            }
        } catch (\Exception $e) {
            \Log::error('Error deleting bill: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting bill: ' . $e->getMessage());
        }
    }

    // In your controller
    public function getUsersByDepartment($departmentId)
    {
        try {
            $authUser = Auth::User();
            $locationId = $authUser->location_id;

            // Get users for this department and location
            $users = User::where('department_id', $departmentId)
                ->where('location_id', $locationId)
                ->where('status', 1)
                ->where('president_flag', 0) // Exclude president users
                ->whereNotNull('start_calendar_id') // Ensure the user has a start_calendar_id
                ->select('id', 'first_name as name', 'role') // Select only needed fields
                ->whereNotIn('role', ['Super Admin', 'Canteen Administrator']) // Exclude specific roles
                ->get();

            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get user details
    public function getUserDetails($userId, $chargeDate)
    {
        try {
            $authUser = Auth::User();
            $locationId = $authUser->location_id;

            $user = User::with('department')
                ->where('id', $userId)
                ->where('location_id', $locationId)
                ->where('status', 1)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $currentStart = Carbon::now()->startOfMonth();
            $currentEnd   = Carbon::now()->endOfMonth();

            $month = $currentStart->format('Y-m');

            $checkExist = Bill::where('user_id', $userId)->where('generate_month', $month)->first();
            if ($checkExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bill already generate for selected user!'
                ], 404);
            }

            $currentDate = Carbon::today()->toDateString();


            if ($chargeDate == $currentDate) {
            } else {
                $allDayStatusId = DayStatus::where('date', '>', $chargeDate)
                    ->where('date', '<=', $currentDate)
                    ->where('location_id', $locationId)
                    ->where('open_flag', 1)
                    ->where('sunday_flag', 0)
                    ->where('holiday_flag', 0)
                    ->pluck('id')
                    ->toArray();


                $totalDietsPresent = AttendanceAbsent::where('user_id', $userId)
                    ->where('location_id', $locationId)
                    ->where('absent_flag', 0)
                    ->whereIn('calendar_id', $allDayStatusId)
                    ->count();


                if ($totalDietsPresent > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The user is present on a date after your selection of Diet Chargeable Date. Make the neccessary correction.'
                    ], 404);
                }

                $presentCheck = AttendanceAbsent::where('user_id', $userId)
                    ->where('location_id', $locationId)
                    ->whereIn('calendar_id', $allDayStatusId)
                    ->count();


                if ($presentCheck <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The user is present on a date after your charge date please change your chargeable date.!'
                    ], 404);
                }
            }




            $startDate = DayStatus::where('id', $user->start_calendar_id)->value('date');

            $summaryCurrentMonth = DayStatus::whereBetween('day_statuses.date', [
                $currentStart->format('Y-m-d'),
                $currentEnd->format('Y-m-d')
            ])
                ->where('day_statuses.date', '>=', $startDate)
                ->leftJoin('attendance_absents', function ($join)  use ($user, $locationId) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.location_id', $locationId)
                        ->where('attendance_absents.user_id', $user->id);
                })
                ->where('date', '<=', Carbon::today()->toDateString())
                ->where('day_statuses.sunday_flag', 0)
                ->where('day_statuses.holiday_flag', 0)
                ->where('day_statuses.open_flag', 1)
                ->where('day_statuses.location_id', $locationId)
                ->selectRaw("
            SUM(CASE WHEN attendance_absents.absent_flag = 1 THEN 1 ELSE 0 END) as absent_days
        ")
                ->first();

            $monthDayCount = DayStatus::whereBetween('day_statuses.date', [
                $currentStart->format('Y-m-d'),
                $currentEnd->format('Y-m-d')
            ])

                ->where('day_statuses.date', '>=', $startDate)
                ->whereDate('day_statuses.date', '<=', Carbon::today())
                ->where('day_statuses.holiday_flag', 0)
                ->where('day_statuses.location_id', $locationId)
                ->where('day_statuses.open_flag', 1)->count();

            $presentDays = ($monthDayCount - $summaryCurrentMonth->absent_days);

            $previousMonthRate = RateMaster::where('location_id', $locationId)
                ->whereDate('effective_from_date', '<=', $currentStart->format('Y-m-d'))
                ->where('status', 1)
                ->orderBy('effective_from_date', 'desc')
                ->first();

            if ($user->role == 'Member') {
                $rate = $previousMonthRate->member_rate ?? 0;
            } else if ($user->role == 'Non Member') {
                $rate = $previousMonthRate->non_member_rate ?? 0;
            }

            $currentOutstanding = Bill::where('user_id', $userId)->latest()->value('balance') ?? 0;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->first_name,
                    'email' => $user->email,
                    'phone' => $user->mobile ?? '',
                    'role' => $user->role ?? '',
                    'department' => $user->department->name ?? '',
                    'security_deposit' => $user->security_amount ?? 0,
                    'total_diets' =>  $presentDays,
                    'previous_month_rate' =>  $rate ?? 0,
                    'total_due' =>  $presentDays * $rate ?? 0,
                    'settlement_amount' => ($presentDays * $rate) - $user->security_amount ?? 0,
                    'current_outstanding' => $currentOutstanding
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching user details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user details'
            ], 500);
        }
    }

    public function monthly(Request $request)
    {
        $authUser = Auth::User();

        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill is not available for this role.');
        }

        if ($request->ajax()) {
            $bills = Bill::where('type', '=', 'Monthly')->where('location_id', $authUser->location_id)->get();

            return DataTables::of($bills)
                ->addIndexColumn()

                // Add user name column
                ->addColumn('user_name', function ($row) {
                    return $row->user ? $row->user->first_name : 'N/A';
                })

                ->editColumn('generate_month', function ($row) {
                    return Carbon::parse($row->generate_month . '-01')->format('M-Y');
                })
                ->editColumn('bill_date', function ($row) {
                    return Carbon::parse($row->bill_date)->format('d-m-Y');
                })

                ->editColumn('total_diets', function ($row) {
                    return number_format($row->total_diets);
                })

                ->editColumn('net_chargeable_diet', function ($row) {
                    return '₹ ' . number_format($row->net_monthly_expenses);
                })

                ->editColumn('per_diet_calculation', function ($row) {
                    return '₹ ' . number_format($row->per_diet_calculation);
                })

                ->addColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge badge-primary">Submitted</span>';
                    } elseif ($row->status == 2) {
                        return '<span class="badge badge-warning">Pending</span>';
                    }
                })

                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<a href="' . route('bill-generate.monthly.show', $row->id) . '"
        class="btn btn-sm btn-info me-1"
        title="View">
        <i class="fa fa-eye"></i>
    </a>';

                    if ($row->status == 2 || $row->status == 0) {
                        $buttons .= '<a href="' . route('bill-generate.monthly.edit', $row->id) . '"
        class="btn btn-sm btn-warning me-1"
        title="Edit">
        <i class="fa fa-edit"></i>
    </a>';


                        $buttons .= '<a href="' . route('bill-generate.monthly.delete', $row->id) . '"
        class="btn btn-sm btn-danger me-1"
        title="Delete">
         <i class="fa fa-trash"></i>
    </a>';
                    } else if ($row->status == 1) {

                        $buttons .= '<a href="' . route('bill-generate.monthly.pdf', $row->id) . '"
    class="btn btn-sm btn-warning me-1"
    title="Billing PDF"
    target="_blank">
    <i class="fa fa-file-pdf"></i>
</a>';

                        $buttons .= '<a href="' . route('bill-generate.guest.monthly.pdf', $row->id) . '"
    class="btn btn-sm btn-info me-1"
    title="Guest PDF"
    target="_blank">
    <i class="fa fa-file-pdf"></i>
</a>';

                        $buttons .= '<a href="' . route('payment.create', $row->id) . '"
        class="btn btn-sm btn-danger me-1"
        title="Payment">
        <i class="fa fa-money-bill-wave"></i>
    </a>';
                    }
                    return '<div class="d-flex" style="gap: 2px;">' . $buttons . '</div>';
                })->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('bill.monthly.index');
    }

    public function monthlyCreate(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with(
                'error',
                'Bill generation is not available for this role.'
            );
        }

        $user = Auth::user();

        if (!$user) {
            return redirect()->back()->with(
                'error',
                'User not found.'
            );
        }

        $locationId = $user->location_id;

        /*
    |--------------------------------------------------------------------------
    | Previous Month
    |--------------------------------------------------------------------------
    */
        $previousStart = Carbon::now()->subMonth()->startOfMonth();
        $previousEnd   = Carbon::now()->subMonth()->endOfMonth();

        $previousMonth = $previousStart->format('Y-m');

        /*
    |--------------------------------------------------------------------------
    | Calendar IDs
    |--------------------------------------------------------------------------
    */
        $calendarIds = DayStatus::whereBetween('date', [
            $previousStart,
            $previousEnd
        ])
            ->where('open_flag', 1)
            ->where('location_id', $locationId)
            ->pluck('id');

        if ($calendarIds->isEmpty()) {
            return redirect()->back()->with(
                'error',
                'No calendar found for previous month.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Active Events For Location
    |--------------------------------------------------------------------------
    */
        $eventIds = LocationEvent::where('location_id', $locationId)
            ->where('status', 1)
            ->pluck('event_id');

        if ($eventIds->isEmpty()) {
            return redirect()->back()->with(
                'error',
                'No active event found for this location.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Check Pending Individual Settlement
    |--------------------------------------------------------------------------
    */
        $checkIndividual = Bill::where('generate_month', $previousMonth)
            ->where('location_id', $locationId)
            ->where('type', 'Individual')
            ->where('status', 2)
            ->count();

        if ($checkIndividual > 0) {
            return redirect()->back()->with(
                'error',
                'One or more individual settlements are pending so please delete or final submit them before generating monthly bill.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Check Existing Monthly Bill
    |--------------------------------------------------------------------------
    */
        $monthlyBill = Bill::where('generate_month', $previousMonth)
            ->where('location_id', $locationId)
            ->where('type', 'Monthly')
            ->whereIn('status', [1, 2])
            ->first();

        if ($monthlyBill) {

            $monthName = $previousStart->format('F Y');

            if ($monthlyBill->status == 2) {
                return redirect()->back()->with(
                    'error',
                    'Monthly Bill for ' . $monthName .
                        ' is already generated and pending. Please delete or edit the existing bill.'
                );
            }

            if ($monthlyBill->status == 1) {
                return redirect()->back()->with(
                    'error',
                    'Monthly bill for ' . $monthName .
                        ' has already been generated.'
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Common Calculation
    |--------------------------------------------------------------------------
    */
        $totalMonthDays = $calendarIds->count();

        /*
    |--------------------------------------------------------------------------
    | Initialize Final Total Variables
    |--------------------------------------------------------------------------
    */
        $totalDiets = 0;

        $individualSettlementDiet = 0;

        $presidentCountAll = 0;

        $guestCount = 0;

        $netChargeableDiet = 0;

        $guestExpense = 0;

        $individualExpense = 0;

        $nonMemberDiet = 0;

        $nonMemberExpenses = 0;

        $memberDiet = 0;

        /*
    |--------------------------------------------------------------------------
    | Multiple Event Calculation
    |--------------------------------------------------------------------------
    */
        $companyParameters = [];

        $rateMasters = [];

        foreach ($eventIds as $eventId) {

            /*
        |--------------------------------------------------------------------------
        | Company Parameter
        |--------------------------------------------------------------------------
        */
            $companyParameter = CompanyParameter::where(
                'location_id',
                $locationId
            )
                ->where('event_id', $eventId)
                ->where('status', 1)
                ->first();

            $companyParameters[$eventId] = $companyParameter;


            /*
        |--------------------------------------------------------------------------
        | Rate Master
        |--------------------------------------------------------------------------
        */
            $rateMaster = RateMaster::where(
                'location_id',
                $locationId
            )
                ->where('event_id', $eventId)
                ->whereDate(
                    'effective_from_date',
                    $previousStart->format('Y-m-d')
                )
                ->where('status', 1)
                ->first();

            if (!$rateMaster) {
                return redirect()->back()->with(
                    'error',
                    'Rate master not found for Event ID ' .
                        $eventId . ' for ' .
                        $previousStart->format('F Y')
                );
            }

            if (
                is_null($rateMaster->non_member_rate) ||
                is_null($rateMaster->guest_rate) ||
                is_null($rateMaster->member_rate)
            ) {
                return redirect()->back()->with(
                    'error',
                    'Rate is not set for Event ID ' .
                        $eventId . ' for ' .
                        $previousStart->format('F Y')
                );
            }

            $rateMasters[$eventId] = $rateMaster;


            /*
        |--------------------------------------------------------------------------
        | Individual Settlement
        |--------------------------------------------------------------------------
        */
            $eventIndividualSettlementDiet = Bill::where(
                'generate_month',
                $previousMonth
            )
                ->where('location_id', $locationId)
                ->where('type', 'Individual')
                ->where('status', 1)
                ->sum('total_diets');

            $individualSettlementDiet += $eventIndividualSettlementDiet;


            /*
        |--------------------------------------------------------------------------
        | Users Already Settled Individually
        |--------------------------------------------------------------------------
        */
            $allSettlementIds = Bill::where(
                'generate_month',
                $previousMonth
            )
                ->where('location_id', $locationId)
                ->where('type', 'Individual')
                ->where('status', 1)
                ->pluck('user_id');


            /*
        |--------------------------------------------------------------------------
        | Member Calculation
        |--------------------------------------------------------------------------
        */
            $allMemberIds = UserLocation::where(
                'user_locations.location_id',
                $locationId
            )
                ->join(
                    'users',
                    'users.id',
                    '=',
                    'user_locations.user_id'
                )
                ->where('users.role', 'Member')
                ->where('users.president_flag', 0)
                ->whereNotIn('users.id', $allSettlementIds)
                ->distinct()
                ->pluck('user_locations.user_id');

            /*
        |--------------------------------------------------------------------------
        | Member Absent
        |--------------------------------------------------------------------------
        */
            $memberDietCountAbsent = AttendanceAbsent::whereIn(
                'calendar_id',
                $calendarIds
            )
                ->where('location_id', $locationId)
                ->whereIn('user_id', $allMemberIds)
                ->where('event_id', $eventId)
                ->where('absent_flag', 1)
                ->count();

            $allUserLocationMember = $allMemberIds->count();

            $totalMonthDaysMealMember =
                $allUserLocationMember * $totalMonthDays;

            $presentCountMember =
                $totalMonthDaysMealMember - $memberDietCountAbsent;

            /*
        |--------------------------------------------------------------------------
        | Add Member Total
        |--------------------------------------------------------------------------
        */
            $memberDiet += $presentCountMember;


            /*
        |--------------------------------------------------------------------------
        | Non Member Calculation
        |--------------------------------------------------------------------------
        */
            $allNonMemberIds = UserLocation::where(
                'user_locations.location_id',
                $locationId
            )
                ->join(
                    'users',
                    'users.id',
                    '=',
                    'user_locations.user_id'
                )
                ->where('users.role', 'Non Member')
                ->where('users.president_flag', 0)
                ->whereNotIn('users.id', $allSettlementIds)
                ->distinct()
                ->pluck('user_locations.user_id');

            /*
        |--------------------------------------------------------------------------
        | Non Member Absent
        |--------------------------------------------------------------------------
        */
            $nonMemberDietCountAbsent = AttendanceAbsent::whereIn(
                'calendar_id',
                $calendarIds
            )
                ->where('location_id', $locationId)
                ->whereIn('user_id', $allNonMemberIds)
                ->where('event_id', $eventId)
                ->where('absent_flag', 1)
                ->count();

            $allUserLocationNonMember = $allNonMemberIds->count();

            $totalMonthDaysMealNonMember =
                $allUserLocationNonMember * $totalMonthDays;

            $presentCountNonMember =
                $totalMonthDaysMealNonMember -
                $nonMemberDietCountAbsent;

            /*
        |--------------------------------------------------------------------------
        | Add Non Member Total
        |--------------------------------------------------------------------------
        */
            $nonMemberDiet += $presentCountNonMember;


            /*
        |--------------------------------------------------------------------------
        | President Calculation
        |--------------------------------------------------------------------------
        */
            $presidentId = User::where('location_id', $locationId)
                ->where('president_flag', 1)
                ->value('id');

            $eventPresidentCount = 0;

            if ($presidentId) {

                $presidentAbsent = AttendanceAbsent::whereIn(
                    'calendar_id',
                    $calendarIds
                )
                    ->where('location_id', $locationId)
                    ->where('user_id', $presidentId)
                    ->where('event_id', $eventId)
                    ->where('absent_flag', 1)
                    ->count();

                $eventPresidentCount =
                    $totalMonthDays - $presidentAbsent;
            }

            /*
        |--------------------------------------------------------------------------
        | Add President Total
        |--------------------------------------------------------------------------
        */
            $presidentCountAll += $eventPresidentCount;


            /*
        |--------------------------------------------------------------------------
        | Guest Calculation
        |--------------------------------------------------------------------------
        */
            $eventGuestCount = Guest::whereIn(
                'calendar_id',
                $calendarIds
            )
                ->where('location_id', $locationId)
                ->where('event_id', $eventId)
                ->sum('guest_count');

            /*
        |--------------------------------------------------------------------------
        | Add Guest Total
        |--------------------------------------------------------------------------
        */
            $guestCount += $eventGuestCount;


            /*
        |--------------------------------------------------------------------------
        | Event Total Diet
        |--------------------------------------------------------------------------
        */
            $eventTotalDiets =
                $presentCountMember +
                $eventPresidentCount +
                $eventGuestCount +
                $eventIndividualSettlementDiet +
                $presentCountNonMember;

            /*
        |--------------------------------------------------------------------------
        | Add Event Total Into Final Total
        |--------------------------------------------------------------------------
        */
            $totalDiets += $eventTotalDiets;


            /*
        |--------------------------------------------------------------------------
        | Event Net Chargeable Diet
        |--------------------------------------------------------------------------
        */
            $eventNetChargeableDiet =
                $eventTotalDiets -
                (
                    $eventPresidentCount +
                    $eventGuestCount +
                    $eventIndividualSettlementDiet +
                    $presentCountNonMember
                );

            /*
        |--------------------------------------------------------------------------
        | Add Net Chargeable Diet
        |--------------------------------------------------------------------------
        */
            $netChargeableDiet += $eventNetChargeableDiet;


            /*
        |--------------------------------------------------------------------------
        | Guest Expense
        |--------------------------------------------------------------------------
        */
            $eventGuestExpense =
                $eventGuestCount * $rateMaster->guest_rate;

            $guestExpense += $eventGuestExpense;


            /*
        |--------------------------------------------------------------------------
        | Individual Expense
        |--------------------------------------------------------------------------
        */
            $eventIndividualExpense = Bill::where(
                'generate_month',
                $previousMonth
            )
                ->where('location_id', $locationId)
                ->where('type', 'Individual')
                ->where('status', 1)
                ->sum('individual_expenses');

            $individualExpense += $eventIndividualExpense;


            /*
        |--------------------------------------------------------------------------
        | Non Member Expense
        |--------------------------------------------------------------------------
        */
            $eventNonMemberExpense =
                $presentCountNonMember *
                $rateMaster->non_member_rate;

            $nonMemberExpenses += $eventNonMemberExpense;
        }


        $rateMasterLunch = RateMaster::where(
            'location_id',
            $locationId
        )
            ->where('event_id', 2)
            ->whereDate(
                'effective_from_date',
                $previousStart->format('Y-m-d')
            )
            ->where('status', 1)
            ->first();


        /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */
        return view('bill.monthly.create', [
            'generateMonth' =>
            $previousStart->format('Y-m'),

            'billDate' =>
            now()->format('Y-m-d'),

            'totalMonthDays' =>
            $totalMonthDays,

            /*
        | Final Combined Totals
        */
            'totalDiets' =>
            $totalDiets,

            'individualSettlementDiet' =>
            $individualSettlementDiet,

            'presidentDiet' =>
            $presidentCountAll,

            'guestDiet' =>
            $guestCount,

            'netChargeableDiet' =>
            $netChargeableDiet,

            'guestExpense' =>
            $guestExpense,

            'individualExpense' =>
            number_format($individualExpense, 0),

            'memberDiet' =>
            $memberDiet,

            'nonMemberDiet' =>
            $nonMemberDiet,

            'nonMemberExpenses' =>
            $nonMemberExpenses,

            /*
        | Event-wise data if required later
        */
            'companyParameters' =>
            $companyParameters,

            'rateMaster' =>
            $rateMasterLunch,

            'eventIds' =>
            $eventIds,
        ]);
    }



    public function monthlyStore(Request $request)
    {
        DB::beginTransaction();

        try {
            $authUser = Auth::user();

            // Validate request
            $validated = $request->validate([
                'generate_month' => 'required|date_format:Y-m',
                'bill_date' => 'required|date',
                'total_diets' => 'required|integer|min:0',
                'individual_settlement_diet' => 'required|integer|min:0',
                'guest_diet' => 'required|integer|min:0',
                'non_member_diet' => 'required|integer|min:0',
                'president_diet' => 'required|integer|min:0',
                'net_chargeable_diet' => 'required|integer|min:0',
                'total_expenses' => 'required|numeric|min:0',
                'individual_expenses' => 'required|numeric|min:0',
                'guest_expenses' => 'required|numeric|min:0',
                'net_monthly_expenses' => 'required|numeric|min:1',
                'non_member_expenses' => 'required|numeric|min:1',
                'per_diet_calculation' => 'required|numeric|min:1',
                'per_diet_calculation_manual' => 'required|numeric|min:1',
            ]);

            // Get location details
            $locationId = $authUser->location_id;
            $locationShortCode = Location::where('id', $locationId)->value('short_code');
            $previousMonth = $validated['generate_month'];

            // Generate bill number
            $billNumber = $this->generateBillNumber($locationShortCode);

            // Create main monthly bill
            $monthlyBill = Bill::create([
                'location_id' => $locationId,
                'type' => 'Monthly',
                'generate_date' => Carbon::today(),
                'generate_month' => $previousMonth,
                'calendar_id' => 0,
                'bill_no' => $billNumber,
                'bill_date' => $validated['bill_date'],
                'total_diets' => $validated['total_diets'],
                'individual_set_diet' => $validated['individual_settlement_diet'],
                'president_diet' => $validated['president_diet'],
                'guest_diet' => $validated['guest_diet'],
                'non_member_diet' => $validated['non_member_diet'],
                'net_chargeable_diet' => $validated['net_chargeable_diet'],
                'total_expenses' => $validated['total_expenses'],
                'guest_expenses' => $validated['guest_expenses'],
                'non_member_expenses' => $validated['non_member_expenses'],
                'individual_expenses' => $validated['individual_expenses'],
                'net_monthly_expenses' => $validated['net_monthly_expenses'],
                'per_diet_calculation' => $validated['per_diet_calculation_manual'],
                'per_diet_calculation_auto' => $validated['per_diet_calculation'],
                'status' => 2, // Pending
                'charge_date' => $validated['bill_date'],
                'balance' => 0,
            ]);

            // Update rate master
            $previousStart = Carbon::parse($previousMonth)->startOfMonth();

            // Get all users for this location
            $allUserIds = UserLocation::where('location_id', $locationId)
                ->distinct()
                ->pluck('user_id');

            $alreadySettlementIds = Bill::whereIn('user_id', $allUserIds)
                ->where('generate_month', $previousMonth)
                ->pluck('user_id');

            $remainingUserIds = $allUserIds->diff($alreadySettlementIds)->values();

            $rateMaster = RateMaster::where('location_id', $locationId)
                ->where('event_id', 2)
                ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
                ->where('status', 1)
                ->first();

            if (!$rateMaster) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Rate master not found for ' . $previousStart->format('F Y'));
            }

            // Generate bills for each user
            foreach ($remainingUserIds as $userId) {
                $user = User::find($userId);

                if (!$user) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'User not found with ID: ' . $userId);
                }

                if ($user->president_flag == 1) {
                    continue;
                }

                if ($user->role == 'Member') {
                    $this->createMemberBill($userId, $monthlyBill, $locationId, $previousMonth, $rateMaster);
                } elseif ($user->role == 'Non Member') {
                    $this->createNonMemberBill($userId, $monthlyBill, $locationId, $previousMonth, $rateMaster);
                }
            }

            DB::commit();

            return redirect()
                ->route('bill-generate.monthly.user_list', $monthlyBill->id)
                ->with('success', 'Monthly bill generated successfully with user-wise entries.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e; // Let Laravel handle validation exceptions
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Monthly bill generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'location_id' => $authUser->location_id ?? null,
                'generate_month' => $request->generate_month ?? null
            ]);

            return redirect()->back()
                ->with('error', 'Failed to generate monthly bill. Please try again.')
                ->withInput();
        }
    }



    public function monthlyUserList($id)
    {
        $bill = Bill::with('details', 'details.user')->where('id', $id)->first();
        $bill->details = $bill->details
            ->sortBy(fn($detail) => $detail->user?->first_name)
            ->values();
        return view('bill.monthly.user_list', compact('bill'));
    }
    public function monthlyEdit($id)
    {
        $authUser = Auth::user();

        // Check if user has permission
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'You do not have permission to edit bills.');
        }

        // Find the bill
        $bill = Bill::where('id', $id)->first();

        if (!$bill) {
            return redirect()->back()->with('error', 'Bill not found.');
        }

        // Check if bill is submitted (status != draft)
        if ($bill->status == 1) {
            return redirect()->back()->with('error', 'Submitted bills cannot be editable.');
        }

        $previousMonth = $bill->generate_month;
        $previousStart = Carbon::parse($previousMonth)->startOfMonth();

        $rateMaster = RateMaster::where('location_id', $authUser->location_id)
            ->where('event_id', 2)
            ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
            ->where('status', 1)
            ->first();

        if (!$rateMaster) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Rate master not found for ' . $previousStart->format('F Y'));
        }

        try {
            // Calculate totals (if needed)
            // You might want to calculate these values from the bill data
            $totalDiets = $bill->total_diets ?? 0;
            $individualSettlementDiet = $bill->individual_set_diet ?? 0;
            $guestDiet = $bill->guest_diet ?? 0;
            $nonMemberDiet = $bill->non_member_diet ?? 0;
            $presidentDiet = $bill->president_diet ?? 0;
            $netChargeableDiet = $bill->net_chargeable_diet ?? 0;

            // Expense values
            $individualExpense = $bill->individual_expenses ?? 0;
            $guestExpense = $bill->guest_expenses ?? 0;
            $nonMemberExpenses = $bill->non_member_expenses ?? 0;
            $totalExpenses = $bill->total_expenses ?? 0;

            return view('bill.monthly.edit', compact(
                'bill',
                'totalDiets',
                'individualSettlementDiet',
                'guestDiet',
                'nonMemberDiet',
                'presidentDiet',
                'netChargeableDiet',
                'individualExpense',
                'guestExpense',
                'nonMemberExpenses',
                'totalExpenses',
                'rateMaster',
            ));
        } catch (\Exception $e) {
            \Log::error('Error edit bill: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error edit bill: ' . $e->getMessage());
        }
    }

    public function monthlyUpdate(Request $request, $id)
    {
        $authUser = Auth::user();

        // Check if user has permission
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'You do not have permission to update bills.');
        }

        // Find the existing bill first
        $existingBill = Bill::find($id);

        if (!$existingBill) {
            return redirect()->back()->with('error', 'Bill not found.');
        }

        // Check if bill is already submitted
        if ($existingBill->status == 1) {
            return redirect()->back()->with('error', 'Submitted bills cannot be updated.');
        }

        // Validate request
        $validated = $request->validate([
            'generate_month' => 'required|date_format:Y-m',
            'bill_date' => 'required|date',
            'bill_no' => 'required',
            'total_diets' => 'required|integer|min:0',
            'individual_settlement_diet' => 'required|integer|min:0',
            'guest_diet' => 'required|integer|min:0',
            'non_member_diet' => 'required|integer|min:0',
            'president_diet' => 'required|integer|min:0',
            'net_chargeable_diet' => 'required|integer|min:0',
            'total_expenses' => 'required|numeric|min:0',
            'individual_expenses' => 'required|numeric|min:0',
            'guest_expenses' => 'required|numeric|min:0',
            'net_monthly_expenses' => 'required|numeric|min:1',
            'non_member_expenses' => 'required|numeric|min:1',
            'per_diet_calculation' => 'required|numeric|min:1',
            'per_diet_calculation_manual' => 'required|numeric|min:1',
        ]);

        // Get location details
        $locationId = $authUser->location_id;
        $locationShortCode = Location::where('id', $locationId)->value('short_code');
        $previousMonth = $validated['generate_month'];

        try {
            DB::beginTransaction();

            // Delete existing bill details
            BillDetail::where('bill_id', $id)->delete();

            // Update the existing bill instead of creating new one
            $existingBill->update([
                'type' => 'Monthly',
                'generate_date' => $request->bill_date,
                'generate_month' => $request->generate_month,
                'calendar_id' => 0,
                'bill_no' => $validated['bill_no'],
                'bill_date' => $validated['bill_date'],
                'total_diets' => $validated['total_diets'],
                'individual_set_diet' => $validated['individual_settlement_diet'],
                'president_diet' => $validated['president_diet'],
                'guest_diet' => $validated['guest_diet'],
                'non_member_diet' => $validated['non_member_diet'],
                'net_chargeable_diet' => $validated['net_chargeable_diet'],
                'total_expenses' => $validated['total_expenses'],
                'guest_expenses' => $validated['guest_expenses'],
                'non_member_expenses' => $validated['non_member_expenses'],
                'individual_expenses' => $validated['individual_expenses'],
                'net_monthly_expenses' => $validated['net_monthly_expenses'],
                'per_diet_calculation' => $validated['per_diet_calculation_manual'],
                'per_diet_calculation_auto' => $validated['per_diet_calculation'],
                'status' => 2, // Draft status
                'charge_date' => $validated['bill_date'],
                'balance' => 0,
            ]);

            // Update rate master
            $previousStart = Carbon::parse($previousMonth)->startOfMonth();

            RateMaster::where('location_id', $locationId)
                ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
                ->where('status', 1)
                ->update(['member_rate' => $validated['per_diet_calculation_manual']]);

            // Get all users for this location
            $allUserIds = UserLocation::where('location_id', $locationId)
                ->distinct()
                ->pluck('user_id');


            // Exclude users who already have settlement for this month
            $alreadySettlementIds = Bill::whereIn('user_id', $allUserIds)
                ->where('generate_month', $previousMonth)
                ->where('id', '!=', $id) // Exclude current bill
                ->pluck('user_id');

            $remainingUserIds = $allUserIds->diff($alreadySettlementIds)->values();

            $rateMaster = RateMaster::where('location_id', $locationId)
                ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
                ->where('status', 1)
                ->first();

            if (!$rateMaster) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Rate master not found for ' . $previousStart->format('F Y'));
            }

            // Generate bills for each user
            foreach ($remainingUserIds as $userId) {
                $user = User::find($userId);

                if (!$user) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'User not found with ID: ' . $userId);
                }

                if ($user->president_flag == 1) {
                    continue;
                }

                if ($user->role == 'Member') {
                    $this->createMemberBill($userId, $existingBill, $locationId, $previousMonth, $rateMaster);
                } elseif ($user->role == 'Non Member') {
                    $this->createNonMemberBill($userId, $existingBill, $locationId, $previousMonth, $rateMaster);
                }
            }

            DB::commit();

            return redirect()
                ->route('bill-generate.monthly.user_list', $existingBill->id)
                ->with('success', 'Monthly bill updated successfully with user-wise entries.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating monthly bill: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating bill: ' . $e->getMessage());
        }
    }
    public function monthlyDelete($id)
    {
        $authUser = Auth::user();

        // Check if user has permission
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'You do not have permission to delete bills.');
        }

        // Find the bill
        $bill = Bill::where('id', $id)->first();

        if (!$bill) {
            return redirect()->back()->with('error', 'Bill not found.');
        }

        // Check if bill is submitted (status != draft)
        if ($bill->status == 1) {
            return redirect()->back()->with('error', 'Submitted bills cannot be deleted.');
        }

        try {
            // Delete BillDetail first (foreign key constraint)
            $billDetailDeleted = BillDetail::where('bill_id', $id)->delete();

            // Delete Bill
            $billDeleted = $bill->delete();

            if ($billDeleted) {
                return redirect()->back() // Update route name as needed
                    ->with('success', 'Bill and its details deleted successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to delete bill.');
            }
        } catch (\Exception $e) {
            \Log::error('Error deleting bill: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting bill: ' . $e->getMessage());
        }
    }


    private function createMemberBill($userId, $monthlyBill, $locationId, $previousMonth, $rateMaster)
    {
        try {
            // Parse month dates
            $currentStart = Carbon::parse($previousMonth)->startOfMonth();
            $currentEnd = Carbon::parse($previousMonth)->endOfMonth();

            $eventIdCount = UserEvent::where('user_id', $userId)->count();

            $allEventId = UserEvent::where('user_id', $userId)->pluck('event_id')->toArray();

            // Get present days for the user
            $presentDays = $this->calculatePresentDays($userId, $locationId, $currentStart, $currentEnd,  $eventIdCount, $allEventId);

            // Skip if no present days
            // if ($presentDays <= 0) {
            //     return;
            // }

            // Check if user is president
            $isPresident = User::where('id', $userId)->where('president_flag', 1)->exists();

            $ratePerDiet = $isPresident ? 0 : ($rateMaster->member_rate ?? 0);
            $dietAmount = $presentDays * $ratePerDiet;

            // $preAmount = BillDetail::where('user_id', $userId)
            //     ->where('type', 'Monthly')
            //     ->where('bill_id', '!=', $monthlyBill->id)
            //     ->where('status', 1)
            //     ->orderByDesc('id')
            //     ->value('pre_balance') ?? 0;

            $preAmount = Ledger::where('user_id', $userId)
                ->where('location_id', $locationId)
                ->orderByDesc('id')
                ->value('balance') ?? 0;

            $balanceAmount = ($preAmount +  $dietAmount);

            $roleName = User::where('id', $userId)->value('role');

            // Create Bill Detail
            BillDetail::create([
                'bill_id' => $monthlyBill->id,
                'type' => 'Monthly',
                'user_id' => $userId,
                'role' => $roleName,
                'user_diets' => $presentDays,
                'rate_per_diet' => $ratePerDiet,
                'pre_balance' => $preAmount,
                'bill_amount' => $dietAmount,
                'balance' => $balanceAmount,
                'status' => 1,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating member bill for user ' . $userId . ': ' . $e->getMessage());
            throw $e; // Re-throw to be caught by parent transaction
        }
    }

    private function createNonMemberBill($userId, $monthlyBill, $locationId, $previousMonth, $rateMaster)
    {
        try {
            // Parse month dates
            $currentStart = Carbon::parse($previousMonth)->startOfMonth();
            $currentEnd = Carbon::parse($previousMonth)->endOfMonth();


            $eventIdCount = UserEvent::where('user_id', $userId)->count();

            $allEventId = UserEvent::where('user_id', $userId)->pluck('event_id')->toArray();

            // Get present days for the user
            $presentDays = $this->calculatePresentDays($userId, $locationId, $currentStart, $currentEnd, $eventIdCount, $allEventId);

            // Skip if no present days
            // if ($presentDays <= 0) {
            //     return;
            // }

            // Check if user is president
            $isPresident = User::where('id', $userId)->where('president_flag', 1)->exists();

            $ratePerDiet = $isPresident ? 0 : ($rateMaster->non_member_rate ?? 0);
            $dietAmount = $presentDays * $ratePerDiet;

            $preAmount = Ledger::where('user_id', $userId)
                ->where('location_id', $locationId)
                ->orderByDesc('id')
                ->value('balance') ?? 0;

            $balanceAmount = ($preAmount +  $dietAmount);

            $roleName = User::where('id', $userId)->value('role');

            // Create Bill Detail
            BillDetail::create([
                'bill_id' => $monthlyBill->id,
                'type' => 'Monthly',
                'user_id' => $userId,
                'role' => $roleName,
                'user_diets' => $presentDays,
                'rate_per_diet' => $ratePerDiet,
                'pre_balance' => $preAmount,
                'bill_amount' => $dietAmount,
                'balance' => $balanceAmount,
                'status' => 1,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating non-member bill for user ' . $userId . ': ' . $e->getMessage());
            throw $e; // Re-throw to be caught by parent transaction
        }
    }

    private function calculatePresentDays(
        $userId,
        $locationId,
        $currentStart,
        $currentEnd,
        $eventIdCount,
        $allEventId
    ) {
        try {
            $absentCount = 0;

            // Calculate absent days for all events
            foreach ($allEventId as $eventId) {

                $absentData = DayStatus::whereBetween('day_statuses.date', [
                    $currentStart->format('Y-m-d'),
                    $currentEnd->format('Y-m-d')
                ])
                    ->where('day_statuses.date', '<=', Carbon::today()->toDateString())
                    ->where('day_statuses.sunday_flag', 0)
                    ->where('day_statuses.holiday_flag', 0)
                    ->where('day_statuses.open_flag', 1)
                    ->where('day_statuses.location_id', $locationId)

                    ->leftJoin('attendance_absents', function ($join) use (
                        $userId,
                        $locationId,
                        $eventId
                    ) {
                        $join->on(
                            'day_statuses.id',
                            '=',
                            'attendance_absents.calendar_id'
                        )
                            ->where('attendance_absents.location_id', $locationId)
                            ->where('attendance_absents.user_id', $userId)
                            ->where('attendance_absents.event_id', $eventId)
                            ->where('attendance_absents.absent_flag', 1);
                    })

                    ->selectRaw('COUNT(attendance_absents.id) as absent_days')
                    ->first();

                $absentCount += (int) ($absentData->absent_days ?? 0);
            }

            // Get total working days
            $monthDayCount = DayStatus::whereBetween('day_statuses.date', [
                $currentStart->format('Y-m-d'),
                $currentEnd->format('Y-m-d')
            ])
                ->where('day_statuses.date', '<=', Carbon::today()->toDateString())
                ->where('day_statuses.sunday_flag', 0)
                ->where('day_statuses.holiday_flag', 0)
                ->where('day_statuses.location_id', $locationId)
                ->where('day_statuses.open_flag', 1)
                ->count();

            // Total available diets for all events
            $totalDays = $monthDayCount * $eventIdCount;

            // Present days = total event days - absent days
            $presentDays = $totalDays - $absentCount;

            return max(0, $presentDays);
        } catch (\Exception $e) {
            \Log::error(
                'Error calculating present days for user ' .
                    $userId . ': ' .
                    $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Generate a unique bill number
     */
    private function generateBillNumber($locationShortCode)
    {
        // Get current financial year (e.g., 2026-27)
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;

        // If current month is January-March, financial year is previous year-current year
        if (date('m') >= 1 && date('m') <= 3) {
            $financialYear = ($currentYear - 1) . '-' . $currentYear;
        } else {
            $financialYear = $currentYear . '-' . $nextYear;
        }

        // Get the last bill number for this location and financial year
        $lastBill = Bill::where('bill_no', 'LIKE', $locationShortCode . '/' . $financialYear . '/%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastBill && $lastBill->bill_no) {
            // Extract the numeric part (last 4 digits) and increment
            $parts = explode('/', $lastBill->bill_no);
            $lastNumber = intval(end($parts));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $locationShortCode . '/' . $financialYear . '/' . $newNumber;
    }

    public function monthlyFinalSubmit($id)
    {
        DB::beginTransaction();

        try {
            $authUser = Auth::user();

            $bill = Bill::where('id', $id)->first();

            if (!$bill) {
                DB::rollBack();
                return redirect()->route('bill-generate.monthly')->with('error', 'Bill not found!');
            }

            $billDetails = BillDetail::where('bill_id', $id)->get();

            if ($billDetails->isEmpty()) {
                DB::rollBack();
                return redirect()->route('bill-generate.monthly')->with('error', 'No bill details found!');
            }

            $currentDate = Carbon::now()->format('Y-m-d');

            foreach ($billDetails as $detail) {
                $user = User::find($detail->user_id);

                if (!$user) {
                    DB::rollBack();
                    return redirect()->route('bill-generate.monthly')->with('error', 'User not found for ID: ' . $detail->user_id);
                }

                $calendarId = DayStatus::where('date', $currentDate)
                    ->where('location_id', $user->location_id)
                    ->value('id');

                // Get existing ledger for user
                $existingLedger = Ledger::where('user_id', $user->id)
                    ->where('location_id', $authUser->location_id)
                    ->orderByDesc('id')
                    ->first();

                if ($existingLedger) {
                    $newBalance = $existingLedger->balance + $detail->bill_amount;
                    $due = $detail->bill_amount;
                    $paid = 0;
                } else {
                    $newBalance = $detail->bill_amount;
                    $due = $detail->bill_amount;
                    $paid = 0;
                }

                // Create ledger entry
                $ledger = Ledger::create([
                    'user_id' => $user->id,
                    'location_id' => $authUser->location_id,
                    'bill_id' => $bill->id,
                    'calendar_id' => $calendarId,
                    'date' => $currentDate,
                    'transaction' => 'Monthly Bill',
                    'due' => $due,
                    'paid' => $paid,
                    'balance' => $newBalance,
                    'created_by' => $authUser->id
                ]);

                if (!$ledger) {
                    DB::rollBack();
                    return redirect()->route('bill-generate.monthly')->with('error', 'Failed to create ledger for user: ' . $user->name);
                }
            }

            $securityAmount = User::where('location_id', $authUser->location_id)->where('status', 1)->sum('security_amount');

            // Update bill status
            $updated = Bill::where('id', $id)->update(['status' => 1, 'security_amount' => $securityAmount]);

            if (!$updated) {
                DB::rollBack();
                return redirect()->route('bill-generate.monthly')->with('error', 'Failed to update bill status!');
            }

            // Commit transaction if everything is successful
            DB::commit();

            return redirect()->route('bill-generate.monthly')->with('success', 'Monthly Bill Final Submit Successfully');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            // Log the error for debugging
            \Log::error('Monthly Final Submit Error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine() . ' in ' . $e->getFile());

            return redirect()->route('bill-generate.monthly')->with('error', 'Something went wrong! Please try again. Error: ' . $e->getMessage());
        }
    }

    public function monthlyShow($id)
    {
        $bill = Bill::with('details', 'details.user')->where('id', $id)->first();
        $bill->details = $bill->details
            ->sortBy(fn($detail) => $detail->user?->first_name)
            ->values();
        return view('bill.monthly.show', compact('bill'));
    }


    public function monthlyPdf($id)
    {
        $authUser = Auth::user();
        $bill = Bill::findOrFail($id);

        if ($authUser->location_id = 1) {

            $billDetails = BillDetail::select('bill_details.*')
                ->join('users', 'users.id', '=', 'bill_details.user_id')
                ->where('bill_details.bill_id', $id)
                ->where('bill_details.user_diets', '!=', 0)
                ->with('user')
                ->orderBy('users.first_name', 'asc')
                ->get();
        } else {
            $billDetails = BillDetail::select('bill_details.*')
                ->join('users', 'users.id', '=', 'bill_details.user_id')
                ->where('bill_details.bill_id', $id)
                ->with('user')
                ->orderBy('users.first_name', 'asc')
                ->get();
        }



        $pdf = Pdf::loadView('bill.monthly.pdf', compact(
            'bill',
            'billDetails'
        ));

        return $pdf->download('monthly-bill-' . Carbon::parse($bill->generate_month)->format('F Y') . '.pdf');
    }

    public function guestPdf($id)
    {
        // Fetch the bill
        $bill = Bill::findOrFail($id);

        // Fetch bill details
        $billDetails = BillDetail::select('bill_details.*')
            ->join('users', 'users.id', '=', 'bill_details.user_id')
            ->where('bill_details.bill_id', $id)
            ->with('user')
            ->orderBy('users.first_name', 'asc')
            ->get();

        // Calculate guest expense data
        $guestDiet = $bill->guest_diet ?? 0;
        $guestExpenses = $bill->guest_expenses ?? 0;
        $mealRatePerGuest = $guestDiet > 0 ? $guestExpenses / $guestDiet : 0;

        // Convert total amount to words
        $totalAmountInWords = $this->numberToWords($guestExpenses);

        $canteenAdministrator = User::where('president_flag', 1)->where('location_id', $bill->location_id)->value('first_name');

        $locationName = Location::where('id', $bill->location_id)->value('name');

        // Get the month range
        $startDate = Carbon::parse($bill->generate_month)->startOfMonth();
        $endDate = Carbon::parse($bill->generate_month)->endOfMonth();

        // Get actual data with guests (your existing query)
        $guestData = Guest::whereIn('calendar_id', function ($query) use ($bill) {
            $query->select('id')
                ->from('day_statuses')
                ->whereBetween('date', [
                    Carbon::parse($bill->generate_month)->startOfMonth()->format('Y-m-d'),
                    Carbon::parse($bill->generate_month)->endOfMonth()->format('Y-m-d')
                ])
                ->where('location_id', $bill->location_id);
        })
            ->where('location_id', $bill->location_id)
            ->selectRaw('DATE(date) as date, SUM(guest_count) as total_guests')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date'); // Key by date for easy lookup

        // Create full month range with zero values
        $completeMonthData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');

            $completeMonthData[] = (object) [
                'date' => $dateString,
                'date_formatted' => $currentDate->format('d-M-Y'),
                'total_guests' => isset($guestData[$dateString]) ? $guestData[$dateString]->total_guests : 0,
                'rate_per_guest' => $mealRatePerGuest,
                'amount' => isset($guestData[$dateString]) ? $guestData[$dateString]->total_guests * $mealRatePerGuest : 0
            ];

            $currentDate->addDay();
        }

        // Convert to collection
        $guestData = collect($completeMonthData);
        $sumTotalGuests = $guestData->sum('total_guests');
        $sumTotalAmount = $guestData->sum('amount');

        // Prepare data for view
        $data = [
            'bill' => $bill,
            'billDetails' => $billDetails,
            'guestDiet' => $guestDiet,
            'guestExpenses' => $guestExpenses,
            'mealRatePerGuest' => $mealRatePerGuest,
            'totalAmountInWords' => $totalAmountInWords,
            'generatedDate' => Carbon::now()->format('d-m-Y'),
            'monthYear' => Carbon::parse($bill->generate_month)->format('F Y'),
            'canteenAdministrator' => $canteenAdministrator,
            'locationName' => $locationName,
            'guestData' => $guestData,
            'sumTotalGuests' => $sumTotalGuests,
            'sumTotalAmount' => $sumTotalAmount
        ];

        // Generate PDF
        $pdf = Pdf::loadView('bill.monthly.guest_pdf', $data);

        // Download PDF
        return $pdf->download('Guest-Charges-' . Carbon::parse($bill->generate_month)->format('F Y') . '.pdf');
    }

    /**
     * Convert number to words (Indian Rupees format)
     */
    private function numberToWords($number)
    {
        $number = (int) $number;

        if ($number == 0) {
            return 'Zero';
        }

        $words = [
            '0' => '',
            '1' => 'One',
            '2' => 'Two',
            '3' => 'Three',
            '4' => 'Four',
            '5' => 'Five',
            '6' => 'Six',
            '7' => 'Seven',
            '8' => 'Eight',
            '9' => 'Nine',
            '10' => 'Ten',
            '11' => 'Eleven',
            '12' => 'Twelve',
            '13' => 'Thirteen',
            '14' => 'Fourteen',
            '15' => 'Fifteen',
            '16' => 'Sixteen',
            '17' => 'Seventeen',
            '18' => 'Eighteen',
            '19' => 'Nineteen',
            '20' => 'Twenty',
            '30' => 'Thirty',
            '40' => 'Forty',
            '50' => 'Fifty',
            '60' => 'Sixty',
            '70' => 'Seventy',
            '80' => 'Eighty',
            '90' => 'Ninety',
        ];

        $result = '';

        // Crore (1,00,00,000)
        if ($number >= 10000000) {
            $crore = floor($number / 10000000);
            $result .= $this->convertNumberToWords($crore, $words) . ' Crore ';
            $number %= 10000000;
        }

        // Lakh (1,00,000)
        if ($number >= 100000) {
            $lakh = floor($number / 100000);
            $result .= $this->convertNumberToWords($lakh, $words) . ' Lakh ';
            $number %= 100000;
        }

        // Thousand (1,000)
        if ($number >= 1000) {
            $thousand = floor($number / 1000);
            $result .= $this->convertNumberToWords($thousand, $words) . ' Thousand ';
            $number %= 1000;
        }

        // Hundred
        if ($number >= 100) {
            $hundred = floor($number / 100);
            $result .= $this->convertNumberToWords($hundred, $words) . ' Hundred ';
            $number %= 100;
        }

        // Tens and Units
        if ($number > 0) {
            if (!empty($result)) {
                $result .= 'and ';
            }
            $result .= $this->convertNumberToWords($number, $words);
        }

        return trim($result) . ' Only';
    }

    /**
     * Helper function to convert number to words
     */
    private function convertNumberToWords($number, $words)
    {
        if ($number <= 20) {
            return $words[$number];
        }

        $tens = floor($number / 10) * 10;
        $units = $number % 10;

        if ($units == 0) {
            return $words[$tens];
        }

        return $words[$tens] . ' ' . $words[$units];
    }

    /**
     * Alternative: Using a simpler number to words function
     */
    private function numberToWordsSimple($number)
    {
        $number = (int) $number;

        if ($number == 0) {
            return 'Zero Only';
        }

        $hyphen = ' ';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'Negative ';
        $decimal = ' point ';
        $dictionary = [
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety',
            100 => 'Hundred',
            1000 => 'Thousand',
            100000 => 'Lakh',
            10000000 => 'Crore'
        ];

        if ($number < 0) {
            return $negative . $this->numberToWordsSimple(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = floor($number / 10) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = floor($number / 100);
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->numberToWordsSimple($remainder);
                }
                break;
            case $number < 100000:
                $thousands = floor($number / 1000);
                $remainder = $number % 1000;
                $string = $this->numberToWordsSimple($thousands) . ' ' . $dictionary[1000];
                if ($remainder) {
                    $string .= $separator . $this->numberToWordsSimple($remainder);
                }
                break;
            case $number < 10000000:
                $lakhs = floor($number / 100000);
                $remainder = $number % 100000;
                $string = $this->numberToWordsSimple($lakhs) . ' ' . $dictionary[100000];
                if ($remainder) {
                    $string .= $separator . $this->numberToWordsSimple($remainder);
                }
                break;
            default:
                $crores = floor($number / 10000000);
                $remainder = $number % 10000000;
                $string = $this->numberToWordsSimple($crores) . ' ' . $dictionary[10000000];
                if ($remainder) {
                    $string .= $separator . $this->numberToWordsSimple($remainder);
                }
                break;
        }

        if ($fraction !== null && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $digit) {
                $words[] = $dictionary[$digit];
            }
            $string .= implode(' ', $words);
        }

        return $string . ' Only';
    }
}
