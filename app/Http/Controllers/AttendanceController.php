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
use App\Models\EventMaster;
use App\Models\GuestDetail;
use App\Models\LocationEvent;
use App\Models\MultipleLocation;
use App\Models\UserEvent;
use Illuminate\Support\Facades\DB;

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

            $locationId = Location::where('id', $userDataCheck->location_id)->where('status', 1)->value('id');

            $eventIds = UserEvent::where('user_id', $userDataCheck->id)->pluck('event_id')->toArray();

            $eventList = LocationEvent::with('event')
                ->where('location_id', $userDataCheck->location_id)
                ->whereIn('event_id', $eventIds)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } else if ($userDataCheck->role == 'Canteen Incharge' || $userDataCheck->role == 'Canteen Administrator') {
            $userData = User::where('status', 1)->where('location_id', $userDataCheck->location_id)->where('personal_guest_flag', 1)->get();
            $allLinkedDepartment = DepartmentLocation::where('location_id', $userDataCheck->location_id)->pluck('department_id');
            $department = Department::whereIn('id',  $allLinkedDepartment)->where('status', 1)->get();
            $locationId = Location::where('status', 1)->where('id',  $userDataCheck->location_id)->value('id');
            $eventList = LocationEvent::with('event')
                ->where('location_id', $userDataCheck->location_id)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
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
            $eventList = LocationEvent::with('event')
                ->where('location_id', $userDataCheck->location_id)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');

            $locationId = Location::where('status', 1)->where('id',  $userDataCheck->location_id)->value('id');
        }

        return view('admin.guests.create', [
            'guest' => new Guest(),
            'departments' => $department,
            'users' => $userData,
            'selectedDate' => $calendar->date,
            'dayStatus' => $dayStatus,
            'eventList' => $eventList,
            'locationId' => $locationId
        ]);
    }


    // public function guestStore(Request $request)
    // {

    //     $userDataCheck = Auth::user();

    //     if (!$userDataCheck) {
    //         return back()->with('error', 'User not found.');
    //     }

    //     if ($userDataCheck->personal_guest_flag != 1) {
    //         return back()->with('error', 'You are not allowed to schedule guests.');
    //     }
    //     $validator = Validator::make($request->all(), [
    //         'guest_type' => 'required|in:Office Guest,Personal Guest',
    //         'department_id' => 'nullable|exists:departments,id',
    //         'location_id' => 'required|exists:locations,id',
    //         'event_id' => 'required|exists:event_masters,id',
    //         'guest_name' => 'nullable|string|max:255',
    //         'guest_count' => 'required|integer|min:1',
    //         'guest_remarks' => 'nullable|string|max:1000',
    //         'attend_user_id' => 'nullable|exists:users,id',
    //         'calendar_id' => 'required|exists:day_statuses,id',
    //         'date' => 'required|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return back()->withErrors($validator)->withInput();
    //     }
    //     $calendar = DayStatus::where('date', $request->date)->where('location_id', $request->location_id)->where('open_flag', 1)->first();
    //     if (!$calendar) {
    //         return back()->with('error', 'Day status not found for the selected date.')->withInput();
    //     }

    //     $userData = $request->filled('attend_user_id') ? User::find($request->attend_user_id) : null;

    //     $existingGuest = Guest::where('calendar_id', $calendar->id)
    //         ->where('attend_user_id', $request->attend_user_id)
    //         ->where('guest_type', $request->guest_type)
    //         ->sum('guest_count');

    //     $newGuestCount = $existingGuest + $request->guest_count;

    //     if ($userData) {
    //         if ($request->guest_type === 'Personal Guest' && $newGuestCount > $userData->max_personal_guest_allowed) {
    //             return back()->with(
    //                 'error',
    //                 "You have already added {$existingGuest} personal guests. You are trying to add {$request->guest_count} more. Maximum allowed is {$userData->max_personal_guest_allowed}."
    //             )->withInput();
    //         }
    //         if ($request->guest_type === 'Office Guest' && $newGuestCount > $userData->max_office_guest_allowed) {
    //             return back()->with(
    //                 'error',
    //                 "You have already added {$existingGuest} office guests. You are trying to add {$request->guest_count} more. Maximum allowed is {$userData->max_office_guest_allowed}."
    //             )->withInput();
    //         }
    //     }


    //     $companyParameter = CompanyParameter::where('location_id', $request->location_id)->where('status', 1)->first();

    //     $currentTime = Carbon::now();

    //     $lateFlag = 0;

    //     if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
    //         $lateFlag = 1;
    //     }


    //     Guest::create([
    //         'guest_type' => $request->guest_type,
    //         'date' => $calendar->date,
    //         'department_id' => $request->department_id,
    //         'location_id' => $request->location_id,
    //         'event_id' => $request->event_id,
    //         'calendar_id' => $calendar->id,
    //         'guest_name' => $request->guest_name,
    //         'guest_count' => $request->guest_count,
    //         'guest_remarks' => $request->guest_remarks,
    //         'attend_user_id' => $request->attend_user_id,
    //         'late_flag'        => $lateFlag,
    //         'created_by' => $userDataCheck->id,
    //         'status' => 1,
    //     ]);

    //     return redirect()->route('admin.dashboard')->with('success', 'Guest created successfully.');
    // }



    public function guestStore(Request $request)
    {
        $userDataCheck = Auth::user();

        if (!$userDataCheck) {
            return back()->with('error', 'User not found.');
        }

        if ($userDataCheck->personal_guest_flag != 1) {
            return back()->with('error', 'You are not allowed to schedule guests.');
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'guest_type' => 'required|in:Office Guest,Personal Guest',
            'location_id' => 'required|exists:locations,id',
            'event_id' => 'required|exists:event_masters,id',
            'guest_count' => 'required|integer|min:1',
            'guest_remarks' => 'nullable|string|max:1000',
            'calendar_id' => 'required|exists:day_statuses,id',
            'date' => 'required|date',
            'guest_name.*' => 'nullable|string|max:255',
            'department_id.*' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if calendar exists
        $calendar = DayStatus::where('date', $request->date)
            ->where('location_id', $request->location_id)
            ->where('open_flag', 1)
            ->first();

        if (!$calendar) {
            return back()->with('error', 'Day status not found for the selected date.')->withInput();
        }

        // Get guest names and department IDs from array
        $guestNames = $request->input('guest_name', []);
        $departmentIds = $request->input('guest_department_id', []);

        // Ensure we have at least one guest
        if (empty($guestNames)) {
            return back()->with('error', 'Please add at least one guest.')->withInput();
        }

        // Check if guest count matches the number of names
        if (count($guestNames) != $request->guest_count) {
            return back()->with('error', 'Guest count does not match the number of guest names provided.')->withInput();
        }




        // Check for existing guests and capacity
        $userData = $request->filled('attend_user_id') ? User::find($request->attend_user_id) : null;

        if ($userData) {
            $existingGuest = Guest::where('calendar_id', $calendar->id)
                ->where('attend_user_id', $request->attend_user_id)
                ->where('guest_type', $request->guest_type)
                ->sum('guest_count');

            $newGuestCount = $existingGuest + $request->guest_count;

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

        // Check late flag
        $companyParameter = CompanyParameter::where('location_id', $request->location_id)
            ->where('status', 1)
            ->first();

        $currentTime = Carbon::now();
        $lateFlag = 0;

        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }

        // Check if event allows attendance marking today
        if ($userDataCheck->role !== 'Canteen Administrator') {

            $today = Carbon::today()->toDateString();

            if ($calendar->date == $today) {

                $currentTime = Carbon::now()->format('H:i:s');
                $maxTime = $companyParameter->attendance_out_time->format('H:i:s');

                if ($currentTime > $maxTime) {

                    $maxTimeFormatted = Carbon::createFromFormat('H:i:s', $maxTime)
                        ->format('h:i A');

                    return back()->with(
                        'error',
                        "Guest cannot be added after {$maxTimeFormatted}. Please contact your Canteen Administrator if you need to add a guest."
                    );
                }
            }
        }



        // Create main guest record
        $guest = Guest::create([
            'guest_type' => $request->guest_type,
            'date' => $calendar->date,
            'department_id' => $request->department_id ?? null,
            'location_id' => $request->location_id,
            'event_id' => $request->event_id,
            'calendar_id' => $calendar->id,
            'guest_count' => $request->guest_count,
            'guest_remarks' => $request->guest_remarks,
            'attend_user_id' => $request->attend_user_id,
            'late_flag' => $lateFlag,
            'created_by' => $userDataCheck->id,
            'status' => 1,
        ]);

        // Create guest details for each guest
        $guestNamesList = [];
        foreach ($guestNames as $index => $name) {
            // Check if department_id is 0 or empty, then set to null
            $departmentId = isset($departmentIds[$index]) && $departmentIds[$index] != 0 ? $departmentIds[$index] : null;
            $departmentName = null;

            // Get department name if department ID is provided and not 0
            if ($departmentId) {
                $department = Department::find($departmentId);
                $departmentName = $department ? $department->name : null;
            }

            GuestDetail::create([
                'guest_id' => $guest->id,
                'guest_name' => trim($name),
                'department_id' => $departmentId,
                'department_name' => $departmentName,
                'status' => 1,
            ]);

            // Build guest name with department for comma-separated list
            $guestWithDept = trim($name);
            if ($departmentName) {
                $guestWithDept .= " - {$departmentName}";
            }
            $guestNamesList[] = $guestWithDept;
        }

        // Update main guest with comma-separated names
        $mainGuestName = implode(', ', $guestNamesList);
        Guest::where('id', $guest->id)->update(['guest_name' => $mainGuestName]);

        return redirect()->route('admin.dashboard')->with('success', 'Guest created successfully with ' . $request->guest_count . ' guest.');
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
            'event',
        ])->where('calendar_id', $dayStatus->id);

        if ($user->role == 'Admin' || $user->role == 'Super Admin') {
        } elseif ($user->role == 'Canteen President' || $user->role == 'Canteen Incharge' || $user->role == 'Canteen Administrator') {
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

        $guest->load(['calendar', 'guestDetails']);

        $eventList = LocationEvent::with('event')
            ->where('location_id', $userDataCheck->location_id)
            ->where('status', 1)
            ->get()
            ->pluck('event.name', 'event.id');

        if ($userDataCheck->role == 'Member' || $userDataCheck->role == 'Non Member') {
            $location = Location::where('id', $userDataCheck->location_id)->where('status', 1)->get();
            $departmentFetchId = Department::getByDepartment($userDataCheck->location_id)->pluck('department_id')->toArray();
            $department = Department::where('status', 1)->whereIn('id', $departmentFetchId)->get();
            $user = User::where('status', 1)->where('id', $userDataCheck->id)->get();
        } else if ($userDataCheck->role == 'Canteen Incharge' || $userDataCheck->role == 'Canteen Administrator') {
            $location = Location::where('id', $userDataCheck->location_id)->where('status', 1)->get();
            $departmentFetchId = Department::getByDepartment($userDataCheck->location_id)->pluck('department_id')->toArray();
            $department = Department::where('status', 1)->whereIn('id', $departmentFetchId)->get();
            $userData1 = User::where('status', 1)->where('location_id', $userDataCheck->location_id)->pluck('id')->toArray();
            $userData2 = MultipleLocation::where('location_id', $userDataCheck->location_id)->pluck('user_id')->toArray();
            $userIds = array_values(array_unique(array_merge($userData1, $userData2)));
            $user = User::where('status', 1)->whereIn('id', $userIds)->get();
        } else {
            $location = Location::where('status', 1)->get();
            $department = Department::where('status', 1)->get();
            $user = User::where('status', 1)->get();
        }

        // Prepare guest details for view
        $guestDetails = $guest->guestDetails()->get();

        return view('admin.guests.edit', [
            'guest'         => $guest,
            'guestDetails'  => $guestDetails,
            'departments'   => $department,
            'locations'     => $location,
            'users'         => $user,
            'selectedDate'  => optional($guest->calendar)->date,
            'eventList'    => $eventList,
        ]);
    }

    public function guestUpdate(Request $request, Guest $guest)
    {
        $userDataCheck = Auth::user();

        if (!$userDataCheck) {
            return back()->with('error', 'User not found.');
        }

        $validator = Validator::make($request->all(), [
            'guest_type' => 'required|in:Office Guest,Personal Guest',
            'location_id' => 'required|exists:locations,id',
            'calendar_id' => 'required|exists:day_statuses,id',
            'date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'guest_remarks' => 'nullable|string|max:1000',
            'guest_name.*' => 'nullable|string|max:255',
            'department_id.*' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Get guest names and department IDs from array
        $guestNames = $request->input('guest_name', []);
        $departmentIds = $request->input('guest_department_id', []);


        // Ensure we have at least one guest
        if (empty($guestNames)) {
            return back()->with('error', 'Please add at least one guest.')->withInput();
        }

        // Check if guest count matches the number of names
        if (count($guestNames) != $request->guest_count) {
            return back()->with('error', 'Guest count does not match the number of guest names provided.')->withInput();
        }

        // Check for existing guests and capacity
        $userData = User::find($request->attend_user_id ?? $guest->attend_user_id);

        if ($userData) {
            $existingGuest = Guest::where('calendar_id', $request->calendar_id)
                ->where('attend_user_id', $request->attend_user_id)
                ->where('guest_type', $request->guest_type)
                ->where('id', '!=', $guest->id)
                ->sum('guest_count');

            $newGuestCount = $existingGuest + $request->guest_count;

            if ($request->guest_type == 'Personal Guest' && $newGuestCount > $userData->max_personal_guest_allowed) {
                return back()->with(
                    'error',
                    'Maximum ' . $userData->max_personal_guest_allowed .
                        ' personal guests are allowed. Existing: ' . $existingGuest .
                        ', Requested: ' . $request->guest_count .
                        ', Total: ' . $newGuestCount
                )->withInput();
            }

            if ($request->guest_type == 'Office Guest' && $newGuestCount > $userData->max_office_guest_allowed) {
                return back()->with(
                    'error',
                    'Maximum ' . $userData->max_office_guest_allowed .
                        ' office guests are allowed. Existing: ' . $existingGuest .
                        ', Requested: ' . $request->guest_count .
                        ', Total: ' . $newGuestCount
                )->withInput();
            }
        }

        // Check late flag
        $companyParameter = CompanyParameter::where('location_id', $request->location_id)
            ->where('status', 1)
            ->first();

        $currentTime = Carbon::now();
        $lateFlag = 0;

        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }

        // Check if event allows attendance marking today
        if ($userDataCheck->role !== 'Canteen Administrator') {

            $today = Carbon::today()->toDateString();

            $calendar = DayStatus::where('date', $request->date)
                ->where('location_id', $request->location_id)
                ->where('open_flag', 1)
                ->first();

            if ($calendar->date == $today) {

                $currentTime = Carbon::now()->format('H:i:s');
                $maxTime = $companyParameter->attendance_out_time->format('H:i:s');

                if ($currentTime > $maxTime) {

                    $maxTimeFormatted = Carbon::createFromFormat('H:i:s', $maxTime)
                        ->format('h:i A');

                    return back()->with(
                        'error',
                        "Guest cannot be update after {$maxTimeFormatted}. Please contact your Canteen Administrator if you need to add a guest."
                    );
                }
            }
        }

        // Update main guest record
        $guest->update([
            'guest_type' => $request->guest_type,
            'date' => $request->date,
            'department_id' => $request->department_id ?? null,
            'location_id' => $request->location_id,
            'event_id' => $request->event_id ?? $guest->event_id,
            'calendar_id' => $request->calendar_id,
            'guest_count' => $request->guest_count,
            'guest_remarks' => $request->guest_remarks,
            'attend_user_id' => $request->attend_user_id ?? $guest->attend_user_id,
            'late_flag' => $lateFlag,
        ]);

        // Delete existing guest details
        GuestDetail::where('guest_id', $guest->id)->delete();

        // Create new guest details
        $guestNamesList = [];
        foreach ($guestNames as $index => $name) {
            // Check if department_id is 0 or empty, then set to null
            $departmentId = isset($departmentIds[$index]) && $departmentIds[$index] != 0 ? $departmentIds[$index] : null;
            $departmentName = null;

            // Get department name if department ID is provided and not 0
            if ($departmentId) {
                $department = Department::find($departmentId);
                $departmentName = $department ? $department->name : null;
            }

            GuestDetail::create([
                'guest_id' => $guest->id,
                'guest_name' => trim($name),
                'department_id' => $departmentId,
                'department_name' => $departmentName,
                'status' => 1,
            ]);

            // Build guest name with department for comma-separated list
            $guestWithDept = trim($name);
            if ($departmentName) {
                $guestWithDept .= " - {$departmentName}";
            }
            $guestNamesList[] = $guestWithDept;
        }

        // Update main guest with comma-separated names
        $mainGuestName = implode(', ', $guestNamesList);
        Guest::where('id', $guest->id)->update(['guest_name' => $mainGuestName]);

        return redirect()
            ->route('admin.guests.list', ['id' => $request->calendar_id])
            ->with('success', 'Guest updated successfully with ' . $request->guest_count . ' guest.');
    }

    public function destroy(Guest $guest)
    {
        GuestDetail::where('guest_id', $guest->id)->delete();
        $guest->delete();
        return redirect()->back()->with('success', 'Guest deleted successfully.');
    }

    public function markAttendance($id, $eventId)
    {
        $UserData = Auth::user();

        if (!$UserData) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Validation 1: Check if event exists for this user
        $userEvent = UserEvent::where('user_id', $UserData->id)
            ->where('event_id', $eventId)
            ->where('status', 1)
            ->first();

        if (!$userEvent) {
            return back()->with('error', 'Invalid event or you do not have access to this event.');
        }

        // Validation 2: Check if day status exists with conditions
        $dayStatus = DayStatus::where('id', $id)
            ->where('open_flag', 1)
            ->where('lock_flag', 0)
            ->where('sunday_flag', 0)
            ->where('holiday_flag', 0)
            ->first();

        if (!$dayStatus) {
            return back()->with('error', 'Selected day is not available for attendance marking.');
        }

        // Validation 3: Check if calendar and date match (same as above, but we already have $dayStatus)
        $calendar = $dayStatus; // Reuse the existing query result

        // Validation 4: Check if event is active for this location
        $companyParameter = CompanyParameter::where('location_id', $calendar->location_id)
            ->where('event_id', $eventId)
            ->where('status', 1)
            ->first();

        if (!$companyParameter) {
            return back()->with('error', 'This event is not configured for the selected location.');
        }

        // Validation 5: Check if event allows attendance marking today
        $today = Carbon::today()->toDateString();

        if ($calendar->date == $today) {
            $currentTime = Carbon::now()->format('H:i:s');
            $maxTime = $companyParameter->attendance_out_time->format('H:i:s');

            if ($currentTime > $maxTime) {
                $maxTimeFormatted = Carbon::createFromFormat('H:i:s', $maxTime)
                    ->format('h:i A');
                return back()->with('error', "Attendance cannot be marked after {$maxTimeFormatted}. The maximum allowed attendance marking time has been exceeded.");
            }
        }

        // Process attendance marking
        $attendance = AttendanceAbsent::where('calendar_id', $calendar->id)
            ->where('user_id', $UserData->id)
            ->where('event_id', $eventId) // Added event_id condition
            ->first();

        if ($attendance) {
            $newAbsentFlag = $attendance->absent_flag == 1 ? 0 : 1;
            $attendance->update([
                'absent_flag' => $newAbsentFlag,
                'status' => 1,
            ]);
        } else {
            $newAbsentFlag = 1;
            AttendanceAbsent::create([
                'calendar_id' => $calendar->id,
                'user_id' => $UserData->id,
                'event_id' => $eventId, // Added event_id
                'absent_flag' => $newAbsentFlag,
                'location_id' => $calendar->location_id,
                'status' => 1,
            ]);
        }

        // Log attendance
        AttendanceLog::create([
            'calendar_id' => $calendar->id,
            'user_id' => $UserData->id,
            'event_id' => $eventId, // Added event_id
            'absent_flag' => $newAbsentFlag,
            'created_by' => auth()->id(),
            'remarks' => 'Attendance updated',
            'status' => 1,
            'web_app' => 'web',
        ]);

        // Multilocation logic with event_id
        if ($UserData->multilocation_flag == 1) {
            $multiLocationData = MultipleLocation::where('user_id', $UserData->id)->get();

            if ($UserData->location_id == $calendar->location_id) {
                if ($newAbsentFlag == 0) {
                    foreach ($multiLocationData as $location) {
                        AttendanceAbsent::updateOrCreate(
                            [
                                'calendar_id' => $calendar->id,
                                'user_id' => $UserData->id,
                                'location_id' => $location->location_id,
                                'event_id' => $eventId, // Added event_id
                            ],
                            [
                                'absent_flag' => 1,
                                'status' => 1,
                            ]
                        );
                    }
                }
            } else {
                $location = Location::where('id', $UserData->location_id)->first();

                if ($newAbsentFlag == 0) {
                    AttendanceAbsent::updateOrCreate(
                        [
                            'calendar_id' => $calendar->id,
                            'user_id' => $UserData->id,
                            'location_id' => $location->id,
                            'event_id' => $eventId, // Added event_id
                        ],
                        [
                            'absent_flag' => 1,
                            'status' => 1,
                        ]
                    );
                }
            }
        }

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

    public function overrideAttendanceNew(Request $request)
    {
        $userData = Auth::user();

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'event_id' => 'required|exists:event_masters,id',
            'absent_flag' => 'required|in:0,1',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {

            $checkUser = User::find($request->user_id);

            if ($checkUser->status == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is inactive.'
                ]);
            }

            if (is_null($checkUser->start_calendar_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Start date is not set for this user.'
                ]);
            }

            $today = Carbon::today()->toDateString();

            $dayStatus = DayStatus::where('date', $today)
                ->where('location_id', $userData->location_id)
                ->where('open_flag', 1)
                ->first();

            if (!$dayStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Day status not found.'
                ]);
            }

            $companyParameter = CompanyParameter::where('location_id', $userData->location_id)
                ->where('status', 1)
                ->first();

            $lateFlag = 0;

            if (
                $companyParameter &&
                Carbon::now()->gt(Carbon::parse($companyParameter->attendance_out_time))
            ) {
                $lateFlag = 1;
            }

            AttendanceAbsent::updateOrCreate(
                [
                    'calendar_id' => $dayStatus->id,
                    'user_id' => $request->user_id,
                    'location_id' => $userData->location_id,
                    'event_id' => $request->event_id,

                ],
                [
                    'absent_flag'      => $request->absent_flag,
                    'late_flag'        => $lateFlag,
                    'status'           => 1,
                    'override_flag'    => 1,
                    'override_remarks' => $request->remarks,
                    'override_user_id' => $userData->id,
                ]
            );

            AttendanceLog::create([
                'calendar_id' => $dayStatus->id,
                'user_id' => $request->user_id,
                'absent_flag' => $request->absent_flag,
                'event_id' => $request->event_id,
                'created_by' => $userData->id,
                'remarks' => $request->remarks ?: 'Attendance overridden',
                'status' => 1,
                'web_app' => 'web',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance overridden successfully.',
                'absent_flag' => (int)$request->absent_flag
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function toggle(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'event_id' => 'required|exists:event_masters,id',
            'absent_flag' => 'required|in:0,1',
        ]);
        $eventId = $request->event_id;

        $authUser = Auth::user();

        $CompanyParameter = CompanyParameter::where('location_id', $authUser->location_id)->where('event_id', $eventId)->where('status', 1)->first();

        $currentTime = Carbon::now()->format('H:i:s');
        $maxTime = $CompanyParameter->attendance_out_time->format('H:i:s');
        if ($currentTime > $maxTime) {
            $maxTime = Carbon::createFromFormat('H:i:s', $maxTime)
                ->format('h:i A');

            return response()->json([
                'success' => false,
                'message' => "Attendance cannot be marked after {$maxTime}. The maximum allowed attendance marking time has been exceeded."
            ]);
        }

        $userData = User::findOrFail($request->user_id);

        $today = Carbon::today()->toDateString();

        $dayStatus = DayStatus::where('date', $today)
            ->where('location_id', $authUser->location_id)
            ->first();

        if (!$dayStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Day status not found.'
            ], 404);
        }

        $attendance = AttendanceAbsent::updateOrCreate(
            [
                'calendar_id' => $dayStatus->id,
                'user_id'     => $userData->id,
                'location_id' => $userData->location_id,
                'event_id' => $eventId,
            ],
            [
                'absent_flag' => $request->absent_flag == 1 ? 0 : 1,
                'status' => 1,
            ]
        );

        return response()->json([
            'success' => true,
            'absent_flag' => $attendance->absent_flag,
            'message' => $attendance->absent_flag ? 'Marked Absent' : 'Marked Present',
        ]);
    }

    public function calendar()
    {
        $UserData = Auth::user();

        if (($UserData->role == 'Member' || $UserData->role == 'Non Member')) {
            $primaryLocation = collect([
                [
                    'location_id'   => $UserData->location_id,
                    'location_name' => optional($UserData->location)->name,
                ]
            ]);

            $multiLocationData = MultipleLocation::with('location')
                ->where('user_id', $UserData->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'location_id'   => $item->location_id,
                        'location_name' => optional($item->location)->name,
                    ];
                })
                ->values();

            $allLocations = $primaryLocation
                ->merge($multiLocationData)
                ->unique('location_id')
                ->values();

            $locations = $allLocations;

            $eventIds = UserEvent::where('user_id', $UserData->id)->pluck('event_id')->toArray();

            $eventList = LocationEvent::with('event')
                ->where('location_id', $UserData->location_id)
                ->whereIn('event_id', $eventIds)
                ->where('status', 1)
                ->get()
                ->pluck('event.name', 'event.id');
        } else {
            return redirect()->back()->with('error', 'Does not have a permission');
        }

        if ($UserData->start_calendar_id == null) {
            return redirect()->back()->with('error', 'Your start Date is not set please contact to your canteen incharge');
        }


        return view('admin.attendance.calendar', compact('locations', 'eventList'));
    }
    public function calendarEvents(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'event_id' => 'required|exists:event_masters,id',
        ]);

        $userData = Auth::user();
        $locationId = $request->location_id;
        $eventId = $request->event_id;

        $today = Carbon::today('Asia/Kolkata');

        $query = function ($start, $end, $type) use ($userData, $locationId, $eventId) {

            $startDate = null;
            if ($userData->start_calendar_id) {
                $startDateRecord = DayStatus::where('id', $userData->start_calendar_id)
                    ->where('location_id', $locationId)
                    ->first();
                if ($startDateRecord) {
                    $startDate = Carbon::parse($startDateRecord->date);
                }
            }


            $days = DayStatus::whereBetween('day_statuses.date', [
                $start->toDateString(),
                $end->toDateString()
            ])
                ->leftJoin('attendance_absents', function ($join) use ($userData, $locationId, $eventId) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.user_id', $userData->id)
                        ->where('attendance_absents.event_id', $eventId)
                        ->where('attendance_absents.location_id', $locationId);
                })
                ->select(
                    'day_statuses.id',
                    'day_statuses.date',
                    'day_statuses.location_id',
                    'day_statuses.open_flag',
                    'day_statuses.lock_flag',
                    DB::raw('IFNULL(attendance_absents.absent_flag, 0) as absent_flag')
                )
                ->where('day_statuses.location_id', $locationId)
                ->groupBy(
                    'day_statuses.id',
                    'day_statuses.date',
                    'day_statuses.location_id',
                    'day_statuses.open_flag',
                    'day_statuses.lock_flag',
                    'absent_flag'
                )
                ->orderBy('day_statuses.date')
                ->get();

            // Process lock_flag
            foreach ($days as $day) {
                $dayDate = Carbon::parse($day->date);
                if ($startDate && $dayDate->lt($startDate)) {
                    $day->lock_flag = 1;
                }
            }

            // Calculate counts
            $presentCount = $days
                ->where('absent_flag', 0)
                ->where('open_flag', 1)
                ->filter(function ($day) {
                    return Carbon::parse($day->date)->lte(Carbon::today());
                })
                ->count();

            $absentCount = $days
                ->filter(function ($day) use ($userData) {
                    return $day->absent_flag == 1
                        && $day->open_flag == 1
                        && ($userData->start_calendar_id === null || $day->id >= $userData->start_calendar_id)
                        && Carbon::parse($day->date)->lte(Carbon::today());
                })
                ->count();

            $lockedCount = $days
                ->where('lock_flag', 1)
                ->count();

            return [
                $type . 'days' => $days,
                $type . 'Summary' => [
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'locked' => $lockedCount,
                ]
            ];
        };


        $currentMonth = $today->copy();

        $previousMonth = $today
            ->copy()
            ->subMonthNoOverflow();

        $nextMonth = $today
            ->copy()
            ->addMonthNoOverflow();

        return response()->json([
            'status' => true,

            'data' => [

                'previous_month' => $query(
                    $previousMonth->copy()->startOfMonth(),
                    $previousMonth->copy()->endOfMonth(),
                    'previous'
                ),

                'current_month' => $query(
                    $currentMonth->copy()->startOfMonth(),
                    $currentMonth->copy()->endOfMonth(),
                    'current'
                ),

                'next_month' => $query(
                    $nextMonth->copy()->startOfMonth(),
                    $nextMonth->copy()->endOfMonth(),
                    'next'
                ),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $authUser = Auth::user();
        try {
            $request->validate([
                'date' => 'required|date',
                'location_id' => 'required',
                'event_id' => 'required',
                'absent_flag' => 'required|in:0,1'
            ]);

            $CompanyParameter = CompanyParameter::where('location_id', $request->location_id)->where('event_id', $request->event_id)->where('status', 1)->first();

            $userData = User::findOrFail($authUser->id);

            $today = Carbon::today()->toDateString();

            $dayStatus = DayStatus::where('date', $request->date)
                ->where('location_id', $request->location_id)
                ->first();


            if (!$dayStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Day status not found.'
                ], 404);
            }

            $dayStatusCheck = DayStatus::where('date', $today)
                ->where('date', $request->date)
                ->where('location_id', $request->location_id)
                ->first();

            $currentTime = Carbon::now()->format('H:i:s');
            $maxTime = $CompanyParameter->attendance_out_time->format('H:i:s');
            if ($dayStatusCheck && ($currentTime > $maxTime)) {
                $maxTime = Carbon::createFromFormat('H:i:s', $maxTime)
                    ->format('h:i A');

                return response()->json([
                    'success' => false,
                    'message' => "Attendance cannot be marked after {$maxTime}. The maximum allowed attendance marking time has been exceeded."
                ]);
            }


            AttendanceAbsent::updateOrCreate(
                [
                    'calendar_id' => $dayStatus->id,
                    'user_id'     => $userData->id,
                    'location_id' => $request->location_id,
                    'event_id' =>  $request->event_id,
                ],
                [
                    'absent_flag' => $request->absent_flag,
                    'status' => 1,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
