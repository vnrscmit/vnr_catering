<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DayStatus;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\AttendanceAbsent;
use App\Models\AttendanceLog;
use App\Models\CompanyParameter;
use App\Models\Department;
use App\Models\MultipleLocation;

class ApiAttendanceController extends Controller
{
    public function generateYear(Request $request)
    {
        $request->validate([
            'year' => 'required|digits:4',
        ]);

        $year = $request->year;

        if ($year) {
            $checkExist = DayStatus::where('year', $year)->first();
            if ($checkExist) {
                return response()->json([
                    'status'  => false,
                    'message' => "Calendar for {$year} already generated."
                ]);
            }
        }

        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {

            DayStatus::firstOrCreate(
                [
                    'date' => $date->format('Y-m-d')
                ],
                [
                    'day_name'      => $date->format('l'),   // Monday
                    'month'         => $date->format('F'),   // January
                    'day'           => $date->day,
                    'year'          => $date->year,
                    'holiday_flag'  => 0,
                    'sunday_flag'   => $date->isSunday() ? 1 : 0,
                    'open_flag'     => 1,
                    'closed_flag'   => 0,
                    'status'        => 1,
                ]
            );
        }

        return response()->json([
            'status'  => true,
            'message' => "Calendar for {$year} generated successfully."
        ]);
    }

    public function calendar(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
        ]);

        $userData = Auth::user();
        $today = Carbon::today()->toDateString();
        $locationId = $request->location_id;

        $query = function ($start, $end, $type) use ($userData, $today, $locationId) {

            $days = DayStatus::whereBetween('day_statuses.date', [
                $start->toDateString(),
                $end->toDateString()
            ])
                ->leftJoin('attendance_absents', function ($join) use ($userData, $locationId) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.user_id', $userData->id)
                        ->where('attendance_absents.location_id', $locationId);
                })
                ->select(
                    'day_statuses.*',
                    DB::raw('IFNULL(attendance_absents.absent_flag,0) as absent_flag')
                )
                ->orderBy('day_statuses.date')
                ->get();

            return [
                $type . 'days' => $days,
                $type . 'Summary' => [
                    'present' => $days->where('absent_flag', 0)
                        ->where('open_flag', 1)
                        ->where('date', '<=', $today)
                        ->count(),

                    'absent' => $days->where('absent_flag', 1)->count(),

                    'locked' => $days->where('open_flag', 1)
                        ->where('date', '<', $today)
                        ->count(),
                ]
            ];
        };

        return response()->json([
            'status' => true,
            'data' => [
                'previous_month' => $query(
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                    'previous'
                ),

                'current_month' => $query(
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                    'current'
                ),

                'next_month' => $query(
                    Carbon::now()->addMonth()->startOfMonth(),
                    Carbon::now()->addMonth()->endOfMonth(),
                    'next'
                ),
            ]
        ]);
    }

    public function guestCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_type'      => 'required|in:Office Guest,Personal Guest',
            'department_id'   => 'nullable|exists:departments,id',
            'location_id'     => 'required|exists:locations,id',
            'guest_name'      => 'required|string|max:255',
            'guest_count'     => 'required|integer|min:1',
            'guest_remarks'   => 'nullable|string|max:1000',
            'attend_user_id'  => 'nullable|exists:users,id',
            'date'           => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $date = Carbon::parse($request->date)->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $calendarId = DayStatus::where('date', $date)->where('open_flag', 1)->value('id');
        if ($calendarId) {
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Day Status not found.',
            ], 201);
        }

        $userData = User::findOrFail($request->attend_user_id);

        if ($userData) {

            if (
                $request->guest_type === 'Personal Guest' &&
                $request->guest_count > $userData->max_personal_guest_allowed
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can add a maximum of ' . $userData->max_personal_guest_allowed . ' personal guests. You requested ' . $request->guest_count . ' guests.',
                ], 422);
            }

            if (
                $request->guest_type === 'Office Guest' &&
                $request->guest_count > $userData->max_office_guest_allowed
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can add a maximum of ' . $userData->max_office_guest_allowed . ' office guests. You requested ' . $request->guest_count . ' guests.',
                ], 422);
            }
        }

        $companyParameter = CompanyParameter::where('location_id', $request->location_id)->where('status', 1)->first();
        $currentTime = Carbon::now();
        $lateFlag = 0;
        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }

        $guest = Guest::create([
            'guest_type'      => $request->guest_type,
            'department_id'   => $request->department_id,
            'location_id'     => $request->location_id,
            'calendar_id'     => $calendarId,
            'guest_name'      => $request->guest_name,
            'guest_count'     => $request->guest_count,
            'guest_remarks'   => $request->guest_remarks,
            'attend_user_id'  => $request->attend_user_id,
            'status'          => 1,
            'late_flag'        => $lateFlag,

        ]);

        return response()->json([
            'status' => true,
            'message' => 'Guest created successfully.',
            'data' => $guest,
        ], 201);
    }

    public function guestList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate   = Carbon::now()->endOfMonth()->toDateString();

        $dayStatusId = DayStatus::whereBetween('date', [$startDate, $endDate])
            ->pluck('id');

        $guestList = Guest::with([
            'attendUser:id,first_name,role',
        ])
            ->whereIn('calendar_id', $dayStatusId)
            ->where('guests.attend_user_id', $request->user_id)
            ->where('location_id', $request->location_id)
            ->latest()
            ->get();

        $personalGuestCount = $guestList->where('guest_type', 'Personal Guest')->count();
        $officeGuestCount   = $guestList->where('guest_type', 'Office Guest')->count();

        return response()->json([
            'status' => true,
            'message' => 'Guest list fetched successfully.',
            'summary' => [
                'total_guest' => $guestList->count(),
                'personal_guest_count' => $personalGuestCount,
                'office_guest_count' => $officeGuestCount,
            ],
            'data' => $guestList
        ]);
    }

    public function markAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'calendar_id' => 'required|exists:day_statuses,id',
            'user_id' => 'required|exists:users,id',
            'date'        => 'required|date',
            'absent_flag' => 'required',
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userData = User::where('status', 1)->where('id', $request->user_id)->first();
        if ($userData) {
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Check calendar id and date match
        $calendar = DayStatus::where('id', $request->calendar_id)
            ->whereDate('date', $request->date)
            ->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid calendar or date.'
            ], 404);
        }

        $today = Carbon::today()->toDateString();

        $CompanyParameter = CompanyParameter::where('location_id', $request->location_id)->where('status', 1)->first();

        if (!$CompanyParameter) {
            return response()->json([
                'status' => false,
                'message' => 'Company Parameter not set for your location.'
            ], 404);
        }

        if ($calendar->date == $today) {
            $currentTime = Carbon::now()->format('H:i:s');
            $maxTime = $CompanyParameter->attendance_out_time->format('H:i:s');
            if ($currentTime > $maxTime) {
                $maxTime = Carbon::createFromFormat('H:i:s', $maxTime)
                    ->format('h:i A');
                return response()->json([
                    'status' => false,
                    'message' => "Attendance cannot be marked after {$maxTime}. The maximum allowed attendance marking time has been exceeded."
                ], 422);
            }
        }
        AttendanceAbsent::updateOrCreate(
            [
                'calendar_id' => $calendar->id,
                'user_id'     => $userData->id,
                'location_id'   => $request->location_id,
            ],
            [
                'absent_flag' => $request->absent_flag,
                'status'      => 1,
            ]
        );

        AttendanceLog::create([
            'calendar_id' => $calendar->id,
            'user_id'     => $userData->id,
            'absent_flag' => $request->absent_flag,
            'created_by'  => auth()->id(),
            'remarks'     => 'Attendance updated',
            'status'      => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Attendance marked successfully.'
        ]);
    }

    // Canteen Incharge 
    public function manageAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $authUser = Auth::user();
        $locationId = $request->location_id;

        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            return response()->json([
                'status' => false,
                'message' => 'Manage attendance is not available for this role.'
            ], 403);
        }

        $today = Carbon::today()->format('Y-m-d');

        $dayStatus = DayStatus::where('date', $today)->first();

        if (!$dayStatus) {
            return response()->json([
                'status' => false,
                'message' => 'Day status not found for today.',
            ], 404);
        }

        $users = User::where('location_id', $authUser->location_id)
            ->join('departments', 'user.department_id', '=', 'departments.id')
            ->whereNotNull('start_calendar_id')
            ->where('start_calendar_id', '<=', $dayStatus->id)
            ->leftJoin('attendance_absents', function ($join) use ($dayStatus, $locationId) {
                $join->on('users.id', '=', 'attendance_absents.user_id')
                    ->where('attendance_absents.location_id',  $locationId)
                    ->where('attendance_absents.calendar_id', $dayStatus->id);
            })
            ->select(
                'users.id',
                'users.first_name',
                'departments.name as department_name',
                DB::raw('COALESCE(attendance_absents.absent_flag,0) as absent_flag'),
                DB::raw("
                CASE
                    WHEN COALESCE(attendance_absents.absent_flag,0) = 1
                    THEN 'Absent'
                    ELSE 'Present'
                END as attendance_status
            ")
            )
            ->orderBy('users.first_name')
            ->get();

        $presentCount = $users->where('absent_flag', 0)->count();
        $absentCount  = $users->where('absent_flag', 1)->count();

        return response()->json([
            'status' => true,
            'message' => 'Attendance List',
            'data' => [
                'summary' => [
                    'total_users' => $users->count(),
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                ],
                'users' => $users
            ]
        ]);
    }

    public function overrideAttendance(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'attendance_date' => 'required|date|exists:day_statuses,date',
            'status' => 'required|in:0,1',
            'remarks' => 'nullable|string|max:500',
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $locationId = $request->location_id;

            $dayStatus = DayStatus::where('date', $request->attendance_date)->where('open_flag', 1)->first();

            if (!$dayStatus) {
                return response()->json([
                    'status' => false,
                    'message' => 'Day status not found for the selected date.',
                ], 404);
            }

            if ($request->status == 1) {
                $absentFlag = 0;
            } else {
                $absentFlag = 1;
            }

            $attendance = AttendanceAbsent::where('calendar_id', $dayStatus->id)
                ->where('user_id', $request->user_id)
                ->where('location_id', $locationId)
                ->first();

            $companyParameter = CompanyParameter::where('location_id', $locationId)->where('status', 1)->first();
            $currentTime = Carbon::now();

            $lateFlag = 0;

            if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
                $lateFlag = 1;
            }

            if ($attendance) {
                // Update existing record
                $attendance->update([
                    'absent_flag'      => $absentFlag,
                    'status'           => 1,
                    'override_flag'    => 1,
                    'override_remarks' => $request->remarks,
                    'override_user_id' => auth()->id(),
                    'late_flag'        => $lateFlag,
                ]);
            } else {

                // Create new record
                $attendance = AttendanceAbsent::create([
                    'calendar_id'      => $dayStatus->id,
                    'user_id'          => $request->user_id,
                    'absent_flag'      => $absentFlag,
                    'location_id'      => $locationId,
                    'status'           => 1,
                    'override_flag'    => 1,
                    'override_remarks' => $request->remarks,
                    'override_user_id' => auth()->id(),
                    'late_flag'        => $lateFlag,
                ]);
            }
            AttendanceLog::create([
                'calendar_id' => $dayStatus->id,
                'user_id' => $request->user_id,
                'absent_flag' => $absentFlag,
                'created_by' => auth()->id(),
                'remarks' => $request->remarks ? $request->remarks : 'Attendance overridden',
                'status' => 1,
                'web_app' => 'app',
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Attendance Override Successfully',
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Attendance Override Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(), // Production me is line ko remove kar dena
            ], 500);
        }
    }

    public function guestListToday(Request $request)
    {

        $validator =  Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationId = $request->location_id;

        $authUser = Auth::user();

        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            return response()->json([
                'status' => false,
                'message' => 'Manage attendance is not available for this role.'
            ], 403);
        }

        $today = Carbon::today()->format('Y-m-d');

        $dayStatus = DayStatus::where('date', $today)->first();

        if (!$dayStatus) {
            return response()->json([
                'status' => false,
                'message' => 'Day status not found for today.',
            ], 404);
        }


        $singleLinkedUserIds = User::where('location_id', $locationId)
            ->whereNotNull('start_calendar_id')
            ->where('status', 1)
            ->pluck('id');

        $multiLinkedUserIds = MultipleLocation::where('location_id', $locationId)
            ->pluck('user_id');

        $allLinkedUserIds = $singleLinkedUserIds
            ->merge($multiLinkedUserIds)
            ->unique()
            ->values();

        // $guestList = Guest::with([
        //     'attendUser:id,first_name,role,department_id',
        //      'attendUser.department:id,name',
        // ])
        //     ->where('calendar_id', $dayStatus->id)
        //     ->where('location_id', $locationId)
        //     ->whereIn('guests.attend_user_id', $allLinkedUserIds)
        //     ->latest()
        //     ->get();
        $guestList = Guest::with([
            'attendUser:id,first_name,role,department_id',
            'attendUser.department:id,name',
        ])
            ->where('calendar_id', $dayStatus->id)
            ->where('location_id', $locationId)
            ->whereIn('attend_user_id', $allLinkedUserIds)
            ->latest()
            ->get()
            ->map(function ($guest) {

                return [
                    'id' => $guest->id,
                    'guest_type' => $guest->guest_type,
                    'calendar_id' => $guest->calendar_id,
                    'department_id' => $guest->department_id,
                    'location_id' => $guest->location_id,
                    'guest_name' => $guest->guest_name,
                    'guest_count' => $guest->guest_count,
                    'guest_remarks' => $guest->guest_remarks,
                    'attend_user_id' => $guest->attend_user_id,
                    'attend_user_name' => optional($guest->attendUser)->first_name,
                    'attend_user_role' => optional($guest->attendUser)->role,
                    'department_name' => optional(optional($guest->attendUser)->department)->name,
                    'status' => $guest->status,
                    'late_flag' => $guest->late_flag,
                    'created_time' => optional($guest->created_at)->format('h:i A'),
                ];
            });

        $personalGuestCount = $guestList->where('guest_type', 'Personal Guest')->count();
        $officeGuestCount   = $guestList->where('guest_type', 'Office Guest')->count();

        return response()->json([
            'status' => true,
            'message' => 'Guest list fetched successfully.',
            'summary' => [
                'total_guest' => $guestList->count(),
                'personal_guest_count' => $personalGuestCount,
                'office_guest_count' => $officeGuestCount,
            ],
            'data' => $guestList
        ]);
    }

    public function getDepartment(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationId = $request->location_id;

        $departments = Department::getByDepartment($locationId);

        if ($departments->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No departments found for the selected location.',
                'data' => [],
            ], 404);
        }
        return response()->json([
            'status' => true,
            'message' => 'Departments fetched successfully.',
            'data' => $departments,
        ], 200);
    }

    public function getuserByDepartment(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationId = $request->location_id;
        $departmentId = $request->department_id;

        $userData = User::select('id', 'first_name as name', 'max_personal_guest_allowed', 'max_office_guest_allowed')
            ->where('department_id', $departmentId)
            ->where('status', 1)
            ->where('personal_guest_flag', 1)
            ->get();

        if ($userData->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found for the selected department.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Users fetched successfully.',
            'data' => $userData,
        ], 200);
    }
}
