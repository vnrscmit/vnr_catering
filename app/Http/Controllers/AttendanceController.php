<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\DayStatus;
use Carbon\Carbon;
use App\Models\CompanyParameter;
use App\Models\AttendanceAbsent;
use App\Models\AttendanceLog;
use App\Models\Guest;
use App\Models\Department;
use App\Models\Location;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\DepartmentLocation;
use App\Models\MultipleLocation;

class AttendanceController extends Controller
{


    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }
    public function index(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        $guestsQuery = Guest::with(['calendar', 'attendUser', 'department', 'location'])
            ->when($request->filled('date'), function ($query) use ($date) {
                $query->whereHas('calendar', function ($calendarQuery) use ($date) {
                    $calendarQuery->whereDate('date', $date);
                });
            })
            ->latest();

        $guests = $guestsQuery->get();

        return view('admin.guests.index', [
            'guests' => $guests,
            'selectedDate' => $date,
            'summary' => [
                'total_guest' => $guests->count(),
                'personal_guest_count' => $guests->where('guest_type', 'Personal Guest')->count(),
                'office_guest_count' => $guests->where('guest_type', 'Office Guest')->count(),
            ],
        ]);
    }

    public function guestCreate($id)
    {

        $userDataCheck = Auth::user();

        if (!$userDataCheck) {
            return back()->with('error', 'User not found.');
        }

        if ($userDataCheck->personal_guest_flag != 1) {
            return back()->with('error', 'You are not allowed to schedule guests.');
        }

        $dayStatus = DayStatus::where('id', $id)
            ->where('location_id', $userDataCheck->location_id)
            ->where('open_flag', 1)
            ->where('lock_flag', 0)
            ->where('sunday_flag', 0)
            ->where('holiday_flag', 0)
            ->first();

        if (!$dayStatus) {
            return back()->with('error', 'Selected day not found.');
        }

        // Check calendar id and date match
        $calendar = DayStatus::where('id', $id)->where('location_id', $userDataCheck->location_id)
            ->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid calendar or date.'
            ], 404);
        }

        if ($userDataCheck->role == 'Member' || $userDataCheck->role == 'Non Member' || $userDataCheck->role == 'Canteen President') {
            $userData = User::where('status', 1)->where('id', $userDataCheck->id)->where('personal_guest_flag', 1)->get();
            $department = Department::where('id', $userDataCheck->department_id)->where('status', 1)->get();
            $location = Location::where('id', $userDataCheck->location_id)->where('status', 1)->get();
        } else if ($userDataCheck->role == 'Canteen Incharge') {
            $userData = User::where('status', 1)->where('location_id', $userDataCheck->location_id)->where('personal_guest_flag', 1)->get();
            $allLinkedDepartment = DepartmentLocation::where('location_id', $userDataCheck->location_id)->pluck('department_id');
            $department = Department::whereIn('id',  $allLinkedDepartment)->where('status', 1)->get();
            $location = Location::where('id', $userDataCheck->location_id)->where('status', 1)->get();
        } else {
            $userData = User::where('status', 1)->where('personal_guest_flag', 1)->get();
            $department = Department::where('status', 1)->get();
            if (!$department) {
                return back()->with('error', 'No active department found. Please contact the administrator.');
            }
            $location = Location::where('status', 1)->get();
            if (!$location) {
                return back()->with('error', 'No active location found. Please contact the administrator.');
            }
        }

        return view('admin.guests.create', [
            'guest' => new Guest(),
            'departments' => $department,
            'locations' =>  $location,
            'users' => $userData,
            'selectedDate' => $calendar->date,
            'dayStatus' => $dayStatus,
        ]);
    }


    public function guestStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_type' => 'required|in:Office Guest,Personal Guest',
            'department_id' => 'nullable|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'guest_name' => 'required|string|max:255',
            'guest_count' => 'required|integer|min:1',
            'guest_remarks' => 'nullable|string|max:1000',
            'attend_user_id' => 'nullable|exists:users,id',
            'calendar_id' => 'required|exists:day_statuses,id',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $calendar = DayStatus::where('id', $request->calendar_id)->where('location_id', $request->location_id)->where('open_flag', 1)->first();
        if (!$calendar) {
            return back()->with('error', 'Day status not found for the selected date.')->withInput();
        }

        $userData = $request->filled('attend_user_id') ? User::find($request->attend_user_id) : null;

        $existingGuest = Guest::where('calendar_id', $calendar->id)
            ->where('attend_user_id', $request->attend_user_id)
            ->where('guest_type', $request->guest_type)
            ->sum('guest_count');

        $newGuestCount = $existingGuest + $request->guest_count;

        if ($userData) {
            if ($request->guest_type === 'Personal Guest' && $newGuestCount > $userData->max_personal_guest_allowed) {
                return back()->with(
                    'error',
                    "You have already added {$existingGuest} personal guests. You are trying to add {$request->guest_count} more. Maximum allowed is {$userData->max_personal_guest_allowed}."
                )->withInput();
            }
            if ($request->guest_type === 'Office Guest' && $newGuestCount > $userData->max_office_guest_allowed) {
                return back()->with(
                    'error',
                    "You have already added {$existingGuest} office guests. You are trying to add {$request->guest_count} more. Maximum allowed is {$userData->max_office_guest_allowed}."
                )->withInput();
            }
        }


        $companyParameter = CompanyParameter::where('location_id', $request->location_id)->where('status', 1)->first();

        $currentTime = Carbon::now();

        $lateFlag = 0;

        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }


        Guest::create([
            'guest_type' => $request->guest_type,
            'department_id' => $request->department_id,
            'location_id' => $request->location_id,
            'calendar_id' => $calendar->id,
            'guest_name' => $request->guest_name,
            'guest_count' => $request->guest_count,
            'guest_remarks' => $request->guest_remarks,
            'attend_user_id' => $request->attend_user_id,
            'late_flag'        => $lateFlag,
            'status' => 1,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Guest created successfully.');
    }


    public function guestList($id)
    {
        $user = Auth::user();

        $dayStatus = DayStatus::where('id', $id)
            ->where('location_id', $user->location_id)
            ->where('open_flag', 1)
            ->where('lock_flag', 0)
            ->where('sunday_flag', 0)
            ->where('holiday_flag', 0)
            ->first();

        if (!$dayStatus) {
            return back()->with('error', 'Selected day not found.');
        }

        $query = Guest::with([
            'location:id,name',
            'department:id,name',
            'calendar:id,date',
            'attendUser:id,first_name,role',
        ])->where('calendar_id', $dayStatus->id);

        if ($user->role == 'Admin' || $user->role == 'Super Admin') {
        } elseif ($user->role == 'Canteen President' || $user->role == 'Canteen Incharge') {
            $query->where('location_id', $user->location_id);
        } else {
            $query->where('attend_user_id', $user->id);
        }

        $guestList = $query->latest()->get();

        $summary = [
            'total_guest' => $guestList->sum('guest_count'),
            'personal_guest_count' => $guestList
                ->where('guest_type', 'Personal Guest')
                ->sum('guest_count'),
            'office_guest_count' => $guestList
                ->where('guest_type', 'Office Guest')
                ->sum('guest_count'),
        ];

        return view('admin.guests.list', [
            'guests'       => $guestList,
            'summary'      => $summary,
            'selectedDate' => $dayStatus->date,
        ]);
    }

    public function guestEdit(Guest $guest)
    {

        $userDataCheck = Auth::user();

        $guest->load('calendar');

        if ($userDataCheck->role == 'Member' || $userDataCheck->role == 'Non Member' || $userDataCheck->role == 'Canteen President') {
            $location = Location::where('id', $userDataCheck->location_id)->where('status', 1)->get();
            $departmentFetchId = Department::getByDepartment($userDataCheck->location_id)->pluck('department_id')->toArray();
            $department = Department::where('status', 1)->whereIn('id', $departmentFetchId)->get();
            $user = User::where('status', 1)->where('id', $userDataCheck->id)->get();
        } else if ($userDataCheck->role == 'Canteen Incharge') {
            $location = Location::where('id', $userDataCheck->location_id)->where('status', 1)->get();
            $departmentFetchId = Department::getByDepartment($userDataCheck->location_id)->pluck('department_id')->toArray();
            $department = Department::where('status', 1)->whereIn('id', $departmentFetchId)->get();
            $userData1 = User::where('status', 1)->where('location_id', $userDataCheck->location_id)->pluck('id')->toArray();
            $userData2 = MultipleLocation::where('location_id', $userDataCheck->location_id)->pluck('user_id')->toArray();
            $userIds = array_values(array_unique(array_merge($userData1, $userData2)));
            $user = User::where('status', 1)->where('id', $userDataCheck->id)->get();
        } else {
            $location = Location::where('status', 1)->get();
            $department = Department::where('status', 1)->get();
            $user = User::where('status', 1)->get();
        }
        return view('admin.guests.edit', [
            'guest'         => $guest,
            'departments'   => $department,
            'locations'     => $location,
            'users'         => $user,
            'selectedDate'  => optional($guest->calendar)->date,
        ]);
    }

    public function guestUpdate(Request $request, Guest $guest)
    {
        $validator = Validator::make($request->all(), [
            'guest_type'     => 'required|in:Office Guest,Personal Guest',
            'department_id'  => 'nullable|exists:departments,id',
            'location_id'    => 'required|exists:locations,id',
            'calendar_id'    => 'required|exists:day_statuses,id',
            'guest_name'     => 'required|string|max:255',
            'guest_count'    => 'required|integer|min:1',
            'guest_remarks'  => 'nullable|string|max:1000',
            'attend_user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userData = User::find($request->attend_user_id);


        $existingGuest = Guest::where('calendar_id', $request->calendar_id)
            ->where('attend_user_id', $request->attend_user_id)
            ->where('guest_type', $request->guest_type)
            ->where('id', '!=', $guest->id)
            ->sum('guest_count');

        $newGuestCount = $existingGuest + $request->guest_count;

        if ($userData) {
            if (
                $request->guest_type == 'Personal Guest' &&
                $newGuestCount > $userData->max_personal_guest_allowed
            ) {
                return back()->with(
                    'error',
                    'Maximum ' . $userData->max_personal_guest_allowed .
                        ' personal guests are allowed. Existing: ' . $existingGuest .
                        ', Requested: ' . $request->guest_count .
                        ', Total: ' . $newGuestCount
                )->withInput();
            }

            if (
                $request->guest_type == 'Office Guest' &&
                $newGuestCount > $userData->max_office_guest_allowed
            ) {
                return back()->with(
                    'error',
                    'Maximum ' . $userData->max_office_guest_allowed .
                        ' office guests are allowed. Existing: ' . $existingGuest .
                        ', Requested: ' . $request->guest_count .
                        ', Total: ' . $newGuestCount
                )->withInput();
            }
        }

        $companyParameter = CompanyParameter::where('location_id', $request->location_id)->where('status', 1)->first();

        $currentTime = Carbon::now();

        $lateFlag = 0;

        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }

        $guest->update([
            'guest_type'     => $request->guest_type,
            'calendar_id'    => $request->calendar_id,
            'department_id'  => $request->department_id,
            'location_id'    => $request->location_id,
            'guest_name'     => $request->guest_name,
            'guest_count'    => $request->guest_count,
            'guest_remarks'  => $request->guest_remarks,
            'attend_user_id' => $request->attend_user_id,
            'late_flag' => $lateFlag,
        ]);

        return redirect()
            ->route('admin.guests.list', ['id' => $request->calendar_id])
            ->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest)
    {
        $guest->delete();
        return redirect()->back()->with('success', 'Guest deleted successfully.');
    }

  public function markAttendance($id)
    {
        $UserData = Auth::user();

        if ($UserData) {
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $dayStatus = DayStatus::where('id', $id)
            ->where('location_id', $UserData->location_id)
            ->where('open_flag', 1)
            ->where('lock_flag', 0)
            ->where('sunday_flag', 0)
            ->where('holiday_flag', 0)
            ->first();

        if (!$dayStatus) {
            return back()->with('error', 'Selected day not found.');
        }

        // Check calendar id and date match
        $calendar = DayStatus::where('id', $id)->where('location_id', $UserData->location_id)
            ->first();
            
          

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid calendar or date.'
            ], 404);
        }

        $today = Carbon::today()->toDateString();

        $CompanyParameter = CompanyParameter::where('location_id', $UserData->location_id)->where('status', 1)->first();

        if ($calendar->date == $today) {
            $currentTime = Carbon::now()->format('H:i:s');
            $maxTime = $CompanyParameter->attendance_out_time->format('H:i:s');
            if ($currentTime > $maxTime) {
                $maxTime = Carbon::createFromFormat('H:i:s', $maxTime)
                    ->format('h:i A');
                return back()->with('error', "Attendance cannot be marked after {$maxTime}. The maximum allowed attendance marking time has been exceeded.");
            }
        }

        $attendance = AttendanceAbsent::where('calendar_id', $calendar->id)
            ->where('user_id', $UserData->id)
            ->where('location_id', $UserData->location_id)
            ->first();

        if ($attendance) {
            $newAbsentFlag = $attendance->absent_flag == 1 ? 0 : 1;
            $attendance->update([
                'absent_flag' => $newAbsentFlag,
                'status'      => 1,
            ]);
        } else {

            $newAbsentFlag = 1;
            AttendanceAbsent::create([
                'calendar_id' => $calendar->id,
                'user_id'     => $UserData->id,
                'absent_flag' => $newAbsentFlag,
                 'location_id' => $UserData->location_id,
                'status'      => 1,
            ]);
        }

        // Har baar log insert hoga
        AttendanceLog::create([
            'calendar_id' => $calendar->id,
            'user_id'     => $UserData->id,
            'absent_flag' => $newAbsentFlag,
            'created_by'  => auth()->id(),
            'remarks'     => 'Attendance updated',
            'status'      => 1,
            'web_app'     => 'web',
        ]);

        return back()->with('success', "Attendance marked successfully.");
    }


  public function overrideAttendance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'attendance_date' => 'required|date|exists:day_statuses,date',
            'status' => 'required|in:0,1',
            'remarks' => 'nullable|string|max:500',
        ]);

        $checkUser = User::find($request->user_id);

        if (!$checkUser) {
            return back()->with('error', 'User not found.');
        }

        if ($checkUser->status == 0) {
            return back()->with('error', 'This user is inactive.');
        }

        if (is_null($checkUser->start_calendar_id)) {
            return back()->with('error', 'Start date is not set for this user.');
        }

        $dayStatus = DayStatus::where('date', $request->attendance_date)->where('location_id', $checkUser->location_id)->where('open_flag', 1)->first();

        if (!$dayStatus) {
            return back()->with('error', 'Day status not found for the selected date.');
        }



        $companyParameter = CompanyParameter::where('location_id', $checkUser->location_id)->where('status', 1)->first();

        if ($request->status == 1) {
            $absentFlag = 0;
        } else {
            $absentFlag = 1;
        }

        $attendance = AttendanceAbsent::where('calendar_id', $dayStatus->id)
            ->where('user_id', $request->user_id)
             ->where('location_id', $checkUser->location_id)
            ->first();

        $currentTime = Carbon::now();

        $lateFlag = 0;

        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }

        if ($attendance) {

            // Update existing record
            $attendance->update([
                'absent_flag'      => $absentFlag,
                'late_flag'        => $lateFlag,
                'status'           => 1,
                'override_flag'    => 1,
                'override_remarks' => $request->remarks,
                'override_user_id' => auth()->id(),
            ]);
        } else {

            // Create new record
            $attendance = AttendanceAbsent::create([
                'calendar_id'      => $dayStatus->id,
                'user_id'          => $request->user_id,
                'location_id' =>     $checkUser->location_id,
                'absent_flag'      => $absentFlag,
                'late_flag'        => $lateFlag,
                'status'           => 1,
                'override_flag'    => 1,
                'override_remarks' => $request->remarks,
                'override_user_id' => auth()->id(),
            ]);
        }
        AttendanceLog::create([
            'calendar_id' => $dayStatus->id,
            'user_id' => $request->user_id,
            'absent_flag' => $absentFlag,
            'created_by' => auth()->id(),
            'remarks' => $request->remarks ? $request->remarks : 'Attendance overridden',
            'status' => 1,
            'web_app' => 'web',
        ]);

        return back()->with('success', "Attendance overridden successfully.");
    }
}
