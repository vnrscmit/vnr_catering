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
use App\Models\Location;
use App\Models\MultipleLocation;
use App\Models\RateMaster;
use App\Models\User;
use App\Models\UserLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            $bills = Bill::where('type', '=', 'Monthly')->get();

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
                    }

                    return '<div class="d-flex" style="gap: 2px;">' . $buttons . '</div>';
                })->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('bill.monthly.index');
    }

    public function monthlyCreate(Request $request)
    {

        $authUser = Auth::User();
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Bill generation is not available for this role.');
        }

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


        // Total Calculation
        $totalMonthDays = $calendarIds->count();

        $dietCountAbsent = AttendanceAbsent::whereIn('calendar_id', $calendarIds)
            ->where('location_id', $locationId)
            ->where('absent_flag', 1)
            ->count();

        $allUserLocation = UserLocation::where('location_id', $locationId)->distinct('user_id')->count('user_id');

        $totalMonthDaysMeal =  ($allUserLocation *  $totalMonthDays);

        $presentCountAll = $totalMonthDaysMeal - $dietCountAbsent;

        // Non Member Calculation
        $allNonMemberIds = UserLocation::where('user_locations.location_id', $locationId)
            ->join('users', 'users.id', '=', 'user_locations.user_id')
            ->where('users.role', '=', 'Non Member')
            ->distinct()
            ->pluck('user_locations.user_id');

        $nonMemberdietCountAbsent = AttendanceAbsent::whereIn('calendar_id', $calendarIds)
            ->where('location_id', $locationId)
            ->whereIn('user_id', $allNonMemberIds)
            ->where('absent_flag', 1)
            ->count();


        $allUserLocationNonMember = UserLocation::where('user_locations.location_id', $locationId)
            ->join('users', 'users.id', '=', 'user_locations.user_id')
            ->where('users.role', '=', 'Non Member')
            ->distinct('user_id')
            ->count('user_id');

        $totalMonthDaysMeal =  ($allUserLocationNonMember *  $totalMonthDays);

        $presentCountNonMember = $totalMonthDaysMeal - $nonMemberdietCountAbsent;

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

        $previousMonth = Carbon::now()->subMonth()->startOfMonth()->format('Y-m');
        $individualSettlementDiet = Bill::where('generate_month', $previousMonth)->where('status', 1)->sum('total_diets') ?? 0;

        $totalDiets = $presentCountAll + $presidentCountAll + $guestCount;

        $netChargeableDiet = $totalDiets - ($presidentCountAll + $guestCount + $individualSettlementDiet + $presentCountNonMember);

        $rateMaster = RateMaster::where('location_id', $locationId)
            ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
            ->where('status', 1)
            ->first();

        if (!$rateMaster) {
            return redirect()->back()->with('error', 'Rate master not found for ' . $previousStart->format('F Y'));
        }

        $guestExpense = $guestCount * $rateMaster->guest_rate;

        $individualExpense = Bill::where('generate_month', $previousMonth)->where('status', 1)->sum('individual_expenses') ?? 0;

        $nonMemberExpenses = $presentCountNonMember * $rateMaster->non_member_rate;

        return view('bill.monthly.create', [
            'generateMonth'             => $previousStart->format('Y-m'),
            'billDate'                  => now()->format('Y-m-d'),
            'totalMonthDays'            => $totalMonthDays,
            'totalDiets'                => $totalDiets,
            'individualSettlementDiet'  => $individualSettlementDiet,
            'presidentDiet'             => $presidentCountAll,
            'guestDiet'                 => $guestCount,
            'netChargeableDiet'         => $netChargeableDiet,
            'guestExpense'              => $guestExpense,
            'individualExpense' => number_format($individualExpense, 0),
            'guestRate'                 => $rateMaster->guest_rate,
            'rateMaster'                => $rateMaster,
            'nonMemberDiet'      => $presentCountNonMember,
            'nonMemberExpenses'         => $nonMemberExpenses
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

            RateMaster::where('location_id', $locationId)
                ->whereDate('effective_from_date', $previousStart->format('Y-m-d'))
                ->where('status', 1)
                ->update(['member_rate' => $validated['per_diet_calculation_manual']]);

            // Get all users for this location
            $allUserIds = UserLocation::where('location_id', $locationId)
                ->distinct()
                ->pluck('user_id');

            $alreadySettlementIds = Bill::whereIn('user_id', $allUserIds)
                ->where('generate_month', $previousMonth)
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

    private function createMemberBill($userId, $monthlyBill, $locationId, $previousMonth, $rateMaster)
    {
        try {
            // Parse month dates
            $currentStart = Carbon::parse($previousMonth)->startOfMonth();
            $currentEnd = Carbon::parse($previousMonth)->endOfMonth();

            // Get present days for the user
            $presentDays = $this->calculatePresentDays($userId, $locationId, $currentStart, $currentEnd);

            // Skip if no present days
            if ($presentDays <= 0) {
                return;
            }

            // Check if user is president
            $isPresident = User::where('id', $userId)->where('president_flag', 1)->exists();

            $ratePerDiet = $isPresident ? 0 : ($rateMaster->member_rate ?? 0);
            $dietAmount = $presentDays * $ratePerDiet;

            // Create Bill Detail
            BillDetail::create([
                'bill_id' => $monthlyBill->id,
                'type' => 'Monthly',
                'user_id' => $userId,
                'user_diets' => $presentDays,
                'rate_per_diet' => $ratePerDiet,
                'bill_amount' => $dietAmount,
                'balance' => $dietAmount,
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

            // Get present days for the user
            $presentDays = $this->calculatePresentDays($userId, $locationId, $currentStart, $currentEnd);

            // Skip if no present days
            if ($presentDays <= 0) {
                return;
            }

            // Check if user is president
            $isPresident = User::where('id', $userId)->where('president_flag', 1)->exists();

            $ratePerDiet = $isPresident ? 0 : ($rateMaster->non_member_rate ?? 0);
            $dietAmount = $presentDays * $ratePerDiet;

            // Create Bill Detail
            BillDetail::create([
                'bill_id' => $monthlyBill->id,
                'type' => 'Monthly',
                'user_id' => $userId,
                'user_diets' => $presentDays,
                'rate_per_diet' => $ratePerDiet,
                'bill_amount' => $dietAmount,
                'balance' => $dietAmount,
                'status' => 1,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating non-member bill for user ' . $userId . ': ' . $e->getMessage());
            throw $e; // Re-throw to be caught by parent transaction
        }
    }

    private function calculatePresentDays($userId, $locationId, $currentStart, $currentEnd)
    {
        try {
            // Get absent days
            $absentData = DayStatus::whereBetween('day_statuses.date', [
                $currentStart->format('Y-m-d'),
                $currentEnd->format('Y-m-d')
            ])
                ->where('day_statuses.date', '<=', Carbon::today()->toDateString())
                ->where('day_statuses.sunday_flag', 0)
                ->where('day_statuses.holiday_flag', 0)
                ->where('day_statuses.open_flag', 1)
                ->where('day_statuses.location_id', $locationId)
                ->leftJoin('attendance_absents', function ($join) use ($userId, $locationId) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.location_id', $locationId)
                        ->where('attendance_absents.user_id', $userId);
                })
                ->selectRaw("SUM(CASE WHEN attendance_absents.absent_flag = 1 THEN 1 ELSE 0 END) as absent_days")
                ->first();

            // Get month day count
            $monthDayCount = DayStatus::whereBetween('day_statuses.date', [
                $currentStart->format('Y-m-d'),
                $currentEnd->format('Y-m-d')
            ])
                ->where('day_statuses.holiday_flag', 0)
                ->where('day_statuses.location_id', $locationId)
                ->where('day_statuses.open_flag', 1)
                ->count();

            return max(0, $monthDayCount - ($absentData->absent_days ?? 0));
        } catch (\Exception $e) {
            \Log::error('Error calculating present days for user ' . $userId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function monthlyUserList($id)
    {
        $bill = Bill::with('details', 'details.user')->where('id', $id)->first();

        return view('bill.monthly.user_list', compact('bill'));
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
}
