<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\AttendanceAbsent;
use App\Models\DayStatus;
use App\Models\Department;
use App\Models\DepartmentLocation;
use App\Models\Guest;
use App\Models\LocationEvent;
use App\Models\MultipleLocation;
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

        // Apply department filter
        if ($request->filled('department_id') && $request->department_id !== 'All') {
            $query->where('department_id', $request->department_id);
        }

        $guests = $query->orderBy('date', 'desc')->get();

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
}
