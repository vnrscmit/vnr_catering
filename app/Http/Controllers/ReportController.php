<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\AttendanceAbsent;
use App\Models\Bill;
use App\Models\DayStatus;
use App\Models\Department;
use App\Models\DepartmentLocation;
use App\Models\Guest;
use App\Models\Ledger;
use App\Models\LocationEvent;
use App\Models\MultipleLocation;
use App\Models\RateMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function dailyAttendance(Request $request)
    {
        $authUser = Auth::user();

        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            $user = User::whereIn('id', $authUser->id)->get();
        } else if (in_array($authUser->role, ['Canteen Administrator'])) {
            $singleLinkedUserIds = User::where('location_id', $authUser->location_id)
                ->whereNotNull('start_calendar_id')
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->where('status', 1)
                ->pluck('id');

            $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
                ->where('multiple_locations.location_id', $authUser->location_id)
                ->where('users.status', 1)
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->pluck('multiple_locations.user_id');

            $allLinkedUserIds = $singleLinkedUserIds
                ->merge($multiLinkedUserIds)
                ->unique()
                ->values();

            $user = User::whereIn('id', $allLinkedUserIds)->select('first_name', 'id')->orderBy('first_name', 'Asc')->get();

            $events = LocationEvent::with('event')->where('location_id', $authUser->location_id)->get();
        } else {
            return redirect()->back()->with('error', 'You do not have permission for reports.');
        }


        return view('report.daily_attendance_report', compact('user', 'events'));
    }

    /**
     * Get attendance data for DataTable
     */
    public function getAttendanceData(Request $request)
    {
        $authUser = Auth::user();
        $locationId = $authUser->location_id;

        // Get all users for this location based on role
        $allowedUserIds = $this->getAllowedUserIds($authUser);

        $usersQuery = User::where('status', 1);

        // Apply role-based filtering
        if (!empty($allowedUserIds)) {
            $usersQuery->whereIn('id', $allowedUserIds);
        }

        // Apply user filter if provided and not 'All'
        if ($request->filled('user_id') && $request->user_id !== 'All') {
            $usersQuery->where('id', $request->user_id);
        }

        $users = $usersQuery->orderBy('first_name', 'Asc')->get();

        // Get all day statuses for the location with date filter
        $dayStatusQuery = DayStatus::where('location_id', $locationId);

        if ($request->filled('date')) {
            $dayStatusQuery->whereDate('date', $request->date);
        }

        $dayStatuses = $dayStatusQuery->get();

        // Get attendance absents with filters
        $attendanceQuery = AttendanceAbsent::with('event')->where('location_id', $locationId)
            ->whereIn('calendar_id', $dayStatuses->pluck('id')->toArray());

        // Apply user filter if provided and not 'All'
        if ($request->filled('user_id') && $request->user_id !== 'All') {
            $attendanceQuery->where('user_id', $request->user_id);
        }

        // Apply role-based filtering
        if (!empty($allowedUserIds)) {
            $attendanceQuery->whereIn('user_id', $allowedUserIds);
        }

        // Apply event filter if provided and not 'All'
        if ($request->filled('event_id') && $request->event_id !== 'All') {
            $attendanceQuery->where('event_id', $request->event_id);
        }


        $attendances = $attendanceQuery->get();

        // Prepare data for DataTable
        $formattedData = [];
        $index = 0;

        foreach ($dayStatuses as $dayStatus) {
            foreach ($users as $user) {
                // Find attendance for this user and day
                $attendance = $attendances->first(function ($item) use ($dayStatus, $user, $request) {
                    $match = $item->calendar_id == $dayStatus->id && $item->user_id == $user->id;

                    // Apply event filter if provided
                    if ($request->filled('event_id') && $request->event_id !== 'All') {
                        $match = $match && $item->event_id == $request->event_id;
                    }

                    return $match;
                });

                // Determine status based on logic
                $status = '';
                $eventName = $attendance->event->name ?? 'Lunch';

                if ($attendance) {
                    // Attendance entry exists
                    if ($attendance->absent_flag == 1) {
                        $status = 'Absent';
                    } else {
                        $status = 'Present';
                    }
                } else {
                    // No attendance entry - consider as Present
                    $status = 'Present';
                }

                if ($request->filled('attendance_status') && $request->attendance_status !== 'All') {
                    // If filter is 'Present' and status is not 'Present', skip this record
                    if ($request->attendance_status == 'Present' && $status != 'Present') {
                        continue;
                    }
                    // If filter is 'Absent' and status is not 'Absent', skip this record
                    if ($request->attendance_status == 'Absent' && $status != 'Absent') {
                        continue;
                    }
                }

                $formattedData[] = [
                    'DT_RowIndex' => ++$index,
                    'attendance_date' => $dayStatus->date ? date('d-m-Y', strtotime($dayStatus->date)) : '',
                    'event_name' => $eventName,
                    'user_name' => $user->first_name ?? '',
                    'type' => $user->role ?? '',
                    'status' => $status,
                    'open_flag' => $dayStatus->open_flag,
                    'lock_flag' => $dayStatus->lock_flag,
                    'absent_flag' => $attendance ? $attendance->absent_flag : 0,
                    'calendar_id' => $dayStatus->id,
                    'user_id' => $user->id,
                    'event_id' => $attendance ? $attendance->event_id : null,
                ];
            }
        }

        return DataTables::of($formattedData)
            ->addColumn('status_badge', function ($row) {
                if ($row['status'] == 'Present') {
                    return '<span class="badge bg-primary">Present</span>';
                } elseif ($row['status'] == 'Absent') {
                    return '<span class="badge bg-danger">Absent</span>';
                } elseif ($row['status'] == 'Locked') {
                    return '<span class="badge bg-secondary">Locked</span>';
                } else {
                    return '<span class="badge bg-warning">Pending</span>';
                }
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    /**
     * Get allowed user IDs based on role
     */
    private function getAllowedUserIds($authUser)
    {
        if (in_array($authUser->role, ['Canteen Administrator'])) {
            $singleLinkedUserIds = User::where('location_id', $authUser->location_id)
                ->whereNotNull('start_calendar_id')
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->where('status', 1)
                ->pluck('id')
                ->toArray();

            $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
                ->where('multiple_locations.location_id', $authUser->location_id)
                ->where('users.status', 1)
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->pluck('multiple_locations.user_id')
                ->toArray();

            return array_unique(array_merge($singleLinkedUserIds, $multiLinkedUserIds));
        } elseif (in_array($authUser->role, ['Member', 'Non Member'])) {
            return [$authUser->id];
        } else {
            // Admin/Super Admin - return empty array to show all users
            return [];
        }
    }

    public function guestReport(Request $request)
    {
        $authUser = Auth::user();

        if (in_array($authUser->role, ['Member', 'Non Member'])) {

            $user = User::where('id', $authUser->id)->get();

            // Member / Non Member ke liye department
            $departments = Department::getByDepartment($authUser->location_id);
        } else if (in_array($authUser->role, ['Canteen Administrator'])) {

            $singleLinkedUserIds = User::where('location_id', $authUser->location_id)
                ->whereNotNull('start_calendar_id')
                ->whereNotIn('users.role', [
                    'Admin',
                    'Super Admin',
                    'Canteen Incharge',
                    'Canteen Administrator'
                ])
                ->where('status', 1)
                ->pluck('id');

            $multiLinkedUserIds = MultipleLocation::join(
                'users',
                'multiple_locations.user_id',
                '=',
                'users.id'
            )
                ->where(
                    'multiple_locations.location_id',
                    $authUser->location_id
                )
                ->where('users.status', 1)
                ->whereNotIn('users.role', [
                    'Admin',
                    'Super Admin',
                    'Canteen Incharge',
                    'Canteen Administrator'
                ])
                ->pluck('multiple_locations.user_id');

            $allLinkedUserIds = $singleLinkedUserIds
                ->merge($multiLinkedUserIds)
                ->unique()
                ->values();

            $user = User::whereIn('id', $allLinkedUserIds)
                ->select('first_name', 'id')
                ->orderBy('first_name', 'Asc')
                ->get();

            $events = LocationEvent::with('event')
                ->where('location_id', $authUser->location_id)
                ->get();

            // Get departments for current location
            $departments = Department::getByDepartment($authUser->location_id);
        } else {
            return redirect()->back()->with(
                'error',
                'You do not have permission for reports.'
            );
        }

        return view('report.guest_report', compact(
            'user',
            'events',
            'departments'
        ));
    }

    public function getGuestData(Request $request)
    {
        $authUser = Auth::user();
        $locationId = $authUser->location_id;

        $query = Guest::query()
            ->with(['department', 'createdBy'])
            ->where('location_id', $locationId);

        // Apply From Date filter
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        // Apply To Date filter
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        // Apply Event filter
        if ($request->filled('department_id') && $request->department_id !== 'All') {
            $query->where('department_id', $request->department_id);
        }

        // Apply department filter
        if ($request->filled('event_id') && $request->event_id !== 'All') {
            $query->where('event_id', $request->event_id);
        }

        $guests = $query->orderBy('date', 'Asc')->get();

        // Prepare data for DataTable
        $formattedData = [];
        $index = 0;

        foreach ($guests as $guest) {
            $formattedData[] = [
                'DT_RowIndex' => ++$index,
                'date' => $guest->date ? date('d-m-Y', strtotime($guest->date)) : '',
                'department_name' => $guest->department->name ?? '',
                'guest_name' => $guest->guest_name ?? '',
                'event_name' => $guest->event->name ?? '',
                'guest_count' => $guest->guest_count ?? 0,
                'guest_remarks' => $guest->guest_remarks ?? '',
                'created_by' => $guest->createdBy->first_name ?? '',
            ];
        }

        return DataTables::of($formattedData)
            ->make(true);
    }


    public function ledgerReport()
    {
        $authUser = Auth::user();
        $locationId = $authUser->location_id;
        if ($authUser->role !== 'Canteen Administrator') {
            return redirect()->back()->with('error', 'Amount Ledger Report is not available for this role.');
        }
        $user = User::where('status', 1)->where('location_id', $locationId)->whereIn('role', ['Member', 'Non Member'])
            ->orderBy('first_name', 'Asc')->get();
        return view('report.ledger', compact('user'));
    }

    public function getLedgerData(Request $request)
    {
        $authUser = Auth::user();
        $locationId = $authUser->location_id;

        if ($authUser->role !== 'Canteen Administrator') {
            return response()->json(['error' => 'Amount Ledger Report is not available for this role.'], 403);
        }

        // Check if user_id is provided and not empty
        if (!$request->filled('user_id') || $request->user_id === '') {
            return DataTables::of([])->make(true);
        }

        $query = Ledger::with(['user', 'creator'])
            ->whereHas('user', function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                    ->where('status', 1)
                    ->whereIn('role', ['Member', 'Non Member']);
            });

        // Apply user filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $ledgers = $query->orderBy('date', 'Asc')
            ->orderBy('id', 'Asc')
            ->get();

        // Prepare data for DataTable
        $formattedData = [];
        $index = 0;

        foreach ($ledgers as $ledger) {
            $formattedData[] = [
                'DT_RowIndex' => ++$index,
                'date' => date('d-m-Y', strtotime($ledger->date)),
                'user' => $ledger->user->first_name ?? '',
                'transaction' => $ledger->transaction ?? '-',
                'due' =>  number_format($ledger->due ?? 0),
                'paid' =>  number_format($ledger->paid ?? 0),
                'balance' =>  number_format($ledger->balance ?? 0),
                'balance_raw' => $ledger->balance ?? 0,
                'created_by_name' => $ledger->creator->first_name ?? $ledger->creator->name ?? '-',
            ];
        }

        return DataTables::of($formattedData)->make(true);
    }

    public function collectionExpenseReport(Request $request)
    {
        $authUser = Auth::user();

        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            $user = User::whereIn('id', $authUser->id)->get();
        } else if (in_array($authUser->role, ['Canteen Administrator'])) {
            $singleLinkedUserIds = User::where('location_id', $authUser->location_id)
                ->whereNotNull('start_calendar_id')
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->where('status', 1)
                ->pluck('id');

            $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
                ->where('multiple_locations.location_id', $authUser->location_id)
                ->where('users.status', 1)
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->pluck('multiple_locations.user_id');

            $allLinkedUserIds = $singleLinkedUserIds
                ->merge($multiLinkedUserIds)
                ->unique()
                ->values();

            $user = User::whereIn('id', $allLinkedUserIds)->select('first_name', 'id')->orderBy('first_name', 'Asc')->get();

            $events = LocationEvent::with('event')->where('location_id', $authUser->location_id)->get();
        } else {
            return redirect()->back()->with('error', 'You do not have permission for reports.');
        }

        return view('report.collection_expense', compact('user', 'events'));
    }


    public function getCollectionExpenseData(Request $request)
    {

        $authUser = Auth::user();

        if (!in_array($authUser->role, ['Canteen Administrator'])) {
            return redirect()->back()->with('error', 'Report is not available for your role.');
        }
        $locationId = $authUser->location_id;

        $bill = Bill::with('details.user')
            ->where('location_id', $locationId)
            ->where('status', 1)
            ->get();


        $formattedData = [];
        $index = 0;

        $previousClosing = Null;

        foreach ($bill as $data) {

            $firstDay = Carbon::createFromFormat('Y-m', $data->generate_month)->startOfMonth()->format('Y-m-d');

            $rateMaster = RateMaster::where('location_id', $locationId)
                ->where('event_id', 2)
                ->whereDate('effective_from_date', $firstDay)
                ->where('status', 1)
                ->first();

            $allMemberAmount = $data->details
                ->where('user.role', 'Member')
                ->sum('bill_amount');

            $allNonMemberAmount = $data->details
                ->where('user.role', 'Non Member')
                ->sum('bill_amount');

            $rateNonMember = $data->details
                ->where('user.role', 'Non Member')
                ->value('rate_per_diet');

            $rateMember = $data->details
                ->where('user.role', 'Member')
                ->value('rate_per_diet');

            $rateGuest = $data->details
                ->where('user.role', 'Member')
                ->value('rate_per_diet');

            $guest_expenses = $data->guest_expenses;

            $totalCollected = ($allNonMemberAmount +  $guest_expenses + $allMemberAmount) ?? 0;

            if ($previousClosing  == Null) {
                $openingBalance = ($data->security_amount - $data->total_expenses);
                $totalCollected = ($allNonMemberAmount +  $guest_expenses + $allMemberAmount);
                $closingBalance = $totalCollected + $openingBalance;
            } else {
                $openingBalance = $previousClosing;
                $totalCollected = ($allNonMemberAmount +  $guest_expenses + $allMemberAmount);
                $closingBalance = $totalCollected + $openingBalance;
            }

            $formattedData[] = [
                'DT_RowIndex' => ++$index,
                'month' => !empty($data->generate_month)
                    ? Carbon::createFromFormat('Y-m', $data->generate_month)->format('M-Y')
                    : '',

                'm_diets' => $data->net_chargeable_diet ?? '',
                'm_rate' => $rateMember ?? '',
                'm_amount' => $allMemberAmount ?? '',

                'n_diets' => $data->non_member_diet ?? '',
                'n_rate' =>  $rateNonMember ?? '',
                'n_amount' => $allNonMemberAmount ?? '',

                'n_guest' => $data->guest_diet ?? '',
                'n_guest_rate' =>  $rateMaster->guest_rate ?? '',
                'n_guest_amount' => $guest_expenses ?? '',
                'security_amount'    => $data->security_amount,
                'total_diets' => $data->total_diets ?? '',

                'total_collected' => $totalCollected ?? '',

                'total_expenses' =>  $data->total_expenses ?? '',
                'opening_balance' =>  $openingBalance,
                'closing_balance' => $closingBalance,

                'actual_diet_charges' =>  $data->per_diet_calculation_auto ?? '',

            ];

            $previousClosing = $closingBalance;
        }

        return DataTables::of($formattedData)
            ->make(true);
    }
}
