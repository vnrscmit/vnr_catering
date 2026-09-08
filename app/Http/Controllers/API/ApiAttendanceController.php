<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DayStatus;
use App\Models\UserEvent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
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
use App\Models\EventMaster;
use App\Models\Location;
use App\Models\MultipleLocation;

class ApiAttendanceController extends Controller
{

    public function generateYear(Request $request)
    {
        $request->validate([
            'year' => 'required|digits:4',
        ]);

        $year = $request->year;

        // Check if calendar is already generated for any location
        $checkExist = DayStatus::where('year', $year)->exists();

        if ($checkExist) {
            return response()->json([
                'status'  => false,
                'message' => "Calendar for {$year} already generated."
            ]);
        }

        $locations = Location::where('status', 1)->get();

        $startDate = Carbon::create($year, 1, 1);
        $endDate   = Carbon::create($year, 12, 31);

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {

            foreach ($locations as $location) {

                DayStatus::firstOrCreate(
                    [
                        'date'        => $date->format('Y-m-d'),
                        'location_id' => $location->id,
                    ],
                    [
                        'day_name'      => $date->format('l'),
                        'month'         => $date->format('F'),
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
        }

        return response()->json([
            'status'  => true,
            'message' => "Calendar for {$year} generated successfully for all locations."
        ]);
    }

    // public function calendar(Request $request)
    // {
    //     $request->validate([
    //         'location_id' => 'required|exists:locations,id',
    //     ]);

    //     $userData = Auth::user();

    //     $today = Carbon::today()->toDateString();
    //     $locationId = $request->location_id;

    //     $query = function ($start, $end, $type) use ($userData, $today, $locationId) {

    //         $days = DayStatus::whereBetween('day_statuses.date', [
    //             $start->toDateString(),
    //             $end->toDateString()
    //         ])
    //             ->leftJoin('attendance_absents', function ($join) use ($userData, $locationId) {
    //                 $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
    //                     ->where('attendance_absents.user_id', $userData->id)
    //                     ->where('attendance_absents.location_id', $locationId);
    //             })
    //             ->select(
    //                 'day_statuses.*',
    //                 DB::raw('IFNULL(attendance_absents.absent_flag,0) as absent_flag')
    //             )
    //             ->where('day_statuses.location_id', $locationId)
    //             ->orderBy('day_statuses.date')
    //             ->get();

    //         return [
    //             $type . 'days' => $days,
    //             $type . 'Summary' => [
    //                 'present' => $days->where('absent_flag', 0)
    //                     ->where('open_flag', 1)
    //                     ->where('date', '<=', Carbon::today()->toDateString())
    //                     ->count(),

    //                 'absent' => $days
    //                     ->filter(function ($day) use ($userData) {
    //                         return $day->absent_flag == 1
    //                             && $day->open_flag == 1
    //                             && $day->id >= $userData->start_calendar_id
    //                             && Carbon::parse($day->date)->lte(Carbon::today());
    //                     })
    //                     ->count(),


    //                 'locked' => $days->where('open_flag', 1)
    //                     ->where('date', '<', $today)
    //                     ->count(),
    //             ]
    //         ];
    //     };

    //     return response()->json([
    //         'status' => true,
    //         'data' => [
    //             'previous_month' => $query(
    //                 Carbon::now()->subMonth()->startOfMonth(),
    //                 Carbon::now()->subMonth()->endOfMonth(),
    //                 'previous'
    //             ),

    //             'current_month' => $query(
    //                 Carbon::now()->startOfMonth(),
    //                 Carbon::now()->endOfMonth(),
    //                 'current'
    //             ),

    //             'next_month' => $query(
    //                 Carbon::now()->addMonth()->startOfMonth(),
    //                 Carbon::now()->addMonth()->endOfMonth(),
    //                 'next'
    //             ),
    //         ]
    //     ]);
    // }

    public function calendar(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'event_id'    => 'required|exists:event_masters,id',
        ]);

        $userData   = Auth::user();
        $today      = Carbon::today('Asia/Kolkata');
        $locationId = $request->location_id;
        $eventId    = $request->event_id;

        /*
    |--------------------------------------------------------------------------
    | User Start Calendar Date
    |--------------------------------------------------------------------------
    */
        $startCalendarDate = null;

        if ($userData->start_calendar_id !== null) {

            $startCalendarDate = DayStatus::where(
                'id',
                $userData->start_calendar_id
            )->value('date');

            if ($startCalendarDate) {
                $startCalendarDate = Carbon::parse($startCalendarDate)
                    ->startOfDay();
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Weekly Summary Helper
    |--------------------------------------------------------------------------
    */
        $makeWeekSummary = function (
            $currentWeek,
            $weekStartDate
        ) use (
            $today,
            $userData
        ) {

            $weekDays = collect($currentWeek)
                ->where('open_flag', 1)
                ->values();

            if ($weekDays->isEmpty()) {
                return null;
            }

            $lastDay = $weekDays->last();

            return [
                'start_date' => Carbon::parse($weekStartDate)->format('F j'),

                'end_date' => Carbon::parse($lastDay->date)->format('F j'),

                'days' => $weekDays->count(),

                'present' => $weekDays
                    ->filter(function ($day) use ($today) {

                        return $day->absent_flag == 0
                            && $day->open_flag == 1
                            && Carbon::parse($day->date)->lte($today);
                    })
                    ->count(),

                'absent' => $weekDays
                    ->filter(function ($day) use (
                        $userData,
                        $today
                    ) {

                        return $day->absent_flag == 1
                            && $day->open_flag == 1
                            && $userData->start_calendar_id !== null
                            && $day->id >= $userData->start_calendar_id
                            && Carbon::parse($day->date)->lte($today);
                    })
                    ->count(),
            ];
        };


        /*
    |--------------------------------------------------------------------------
    | Month / Week Summary Function
    |--------------------------------------------------------------------------
    */
        $getWeekSummary = function (
            $start,
            $end,
            $type
        ) use (
            $userData,
            $today,
            $locationId,
            $eventId,
            $makeWeekSummary,
            $startCalendarDate,
        ) {

            /*
        |--------------------------------------------------------------------------
        | Attendance Subquery
        |
        | IMPORTANT:
        | Same calendar_id may have duplicate attendance_absents records.
        | Grouping here ensures only ONE row is joined.
        |--------------------------------------------------------------------------
        */
            $attendanceSubQuery = DB::table('attendance_absents')
                ->select(
                    'calendar_id',
                    DB::raw('MAX(absent_flag) as absent_flag')
                )
                ->where('user_id', $userData->id)
                ->where('event_id', $eventId)
                ->where('location_id', $locationId)
                ->groupBy('calendar_id');


            /*
        |--------------------------------------------------------------------------
        | Holiday Subquery
        |
        | Prevent duplicate day_status rows if multiple holiday records
        | exist for the same calendar/location.
        |--------------------------------------------------------------------------
        */
            $holidaySubQuery = DB::table('holiday_lists')
                ->select(
                    'calendar_id',
                    'location_id',
                    DB::raw('MAX(remarks) as holiday_remarks')
                )
                ->where('location_id', $locationId)
                ->where('status', 1)
                ->groupBy(
                    'calendar_id',
                    'location_id'
                );


            /*
        |--------------------------------------------------------------------------
        | Get Days
        |--------------------------------------------------------------------------
        */
            $days = DayStatus::whereBetween(
                'day_statuses.date',
                [
                    $start->toDateString(),
                    $end->toDateString()
                ]
            )
                ->where(
                    'day_statuses.location_id',
                    $locationId
                )

                ->leftJoinSub(
                    $attendanceSubQuery,
                    'attendance_absents',
                    function ($join) {

                        $join->on(
                            'day_statuses.id',
                            '=',
                            'attendance_absents.calendar_id'
                        );
                    }
                )

                ->leftJoinSub(
                    $holidaySubQuery,
                    'holiday_lists',
                    function ($join) {

                        $join->on(
                            'day_statuses.id',
                            '=',
                            'holiday_lists.calendar_id'
                        )
                            ->on(
                                'day_statuses.location_id',
                                '=',
                                'holiday_lists.location_id'
                            );
                    }
                )

                /*
            |--------------------------------------------------------------------------
            | Select
            |--------------------------------------------------------------------------
            */
                ->select(
                    'day_statuses.*',

                    DB::raw(
                        'COALESCE(attendance_absents.absent_flag, 0) as absent_flag'
                    ),

                    'holiday_lists.holiday_remarks'
                )

                ->orderBy(
                    'day_statuses.date',
                    'asc'
                )

                ->get();


            /*
        |--------------------------------------------------------------------------
        | Group Days By Week
        |--------------------------------------------------------------------------
        */
            $weeks = [];

            $currentWeek   = [];
            $weekStartDate = null;


            foreach ($days as $day) {

                $dayDate = Carbon::parse($day->date);

                /*
            |--------------------------------------------------------------------------
            | Lock Days Before User Start Calendar
            |--------------------------------------------------------------------------
            */
                if (
                    $startCalendarDate !== null
                    && $dayDate->lt($startCalendarDate)
                ) {

                    $day->lock_flag = 1;
                }


                /*
            |--------------------------------------------------------------------------
            | Monday = New Week
            |--------------------------------------------------------------------------
            */
                if (
                    $dayDate->dayOfWeek === Carbon::MONDAY
                    || $weekStartDate === null
                ) {

                    /*
                |--------------------------------------------------------------------------
                | Previous Week
                |--------------------------------------------------------------------------
                */
                    if (!empty($currentWeek)) {

                        $weekSummary = $makeWeekSummary(
                            $currentWeek,
                            $weekStartDate
                        );

                        if ($weekSummary !== null) {
                            $weeks[] = $weekSummary;
                        }
                    }


                    /*
                |--------------------------------------------------------------------------
                | Start New Week
                |--------------------------------------------------------------------------
                */
                    $currentWeek   = [];
                    $weekStartDate = $dayDate->copy();
                }


                $currentWeek[] = $day;
            }


            /*
        |--------------------------------------------------------------------------
        | Add Last Week
        |--------------------------------------------------------------------------
        */
            if (!empty($currentWeek)) {

                $weekSummary = $makeWeekSummary(
                    $currentWeek,
                    $weekStartDate
                );

                if ($weekSummary !== null) {
                    $weeks[] = $weekSummary;
                }
            }


            /*
        |--------------------------------------------------------------------------
        | Overall Month Summary
        |--------------------------------------------------------------------------
        */
            $presentCount = $days
                ->where('absent_flag', 0)
                ->where('open_flag', 1)
                ->filter(function ($day) use ($today) {

                    return Carbon::parse($day->date)
                        ->lte($today);
                })
                ->count();


            $absentCount = $days
                ->filter(function ($day) use (
                    $userData,
                    $today
                ) {

                    return $day->absent_flag == 1
                        && $day->open_flag == 1
                        && $userData->start_calendar_id !== null
                        && $day->id >= $userData->start_calendar_id
                        && Carbon::parse($day->date)->lte($today);
                })
                ->count();


            $lockedCount = $days
                ->where('lock_flag', 1)
                ->count();


            /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */
            return [

                $type . 'days' => $days,

                $type . 'Summary' => [

                    'present' => $presentCount,

                    'absent' => $absentCount,

                    'locked' => $lockedCount,

                    $type . 'weeks' => $weeks,
                ],
            ];
        };


        /*
    |--------------------------------------------------------------------------
    | Calculate Months From One Fixed Date
    |--------------------------------------------------------------------------
    */

        $currentMonth = $today->copy();

        $previousMonth = $today
            ->copy()
            ->subMonthNoOverflow();

        $nextMonth = $today
            ->copy()
            ->addMonthNoOverflow();


        /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */
        return response()->json([

            'status' => true,

            'data' => [

                /*
            |--------------------------------------------------------------------------
            | Previous Month
            |--------------------------------------------------------------------------
            */
                'previous_month' => $getWeekSummary(
                    $previousMonth->copy()->startOfMonth(),
                    $previousMonth->copy()->endOfMonth(),
                    'previous'
                ),


                /*
            |--------------------------------------------------------------------------
            | Current Month
            |--------------------------------------------------------------------------
            */
                'current_month' => $getWeekSummary(
                    $currentMonth->copy()->startOfMonth(),
                    $currentMonth->copy()->endOfMonth(),
                    'current'
                ),

                /*
            |--------------------------------------------------------------------------
            | Next Month
            |--------------------------------------------------------------------------
            */
                'next_month' => $getWeekSummary(
                    $nextMonth->copy()->startOfMonth(),
                    $nextMonth->copy()->endOfMonth(),
                    'next'
                ),
            ],
        ]);
    }
    public function guestCreate(Request $request)
    {

        $authUser = Auth::user();
        $validator = Validator::make($request->all(), [
            'guest_type'      => 'required|in:Office Guest,Personal Guest',
            'department_id'   => 'nullable|exists:departments,id',
            'location_id'     => 'required|exists:locations,id',
            'event_id'     => 'required|exists:event_masters,id',
            'guest_name'      => 'nullable|string|max:255',
            'guest_count'     => 'required|integer|min:1',
            'guest_remarks'   => 'nullable|string|max:1000',
            'attend_user_id'  => 'nullable',
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
        $calendarId = DayStatus::where('date', $date)->where('location_id', $request->location_id)->where('open_flag', 1)->value('id');
        $calendarDate = DayStatus::where('date', $date)->where('location_id', $request->location_id)->where('open_flag', 1)->value('date');
        if ($calendarId) {
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Day Status not found.',
            ], 201);
        }

        if ($request->attend_user_id != 0) {
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
        }

        $companyParameter = CompanyParameter::where('location_id', $request->location_id)->where('event_id', $request->event_id)->where('status', 1)->first();
        $currentTime = Carbon::now();
        $lateFlag = 0;
        if ($companyParameter && $currentTime->gt($companyParameter->attendance_out_time)) {
            $lateFlag = 1;
        }

        $guest = Guest::create([
            'guest_type'      => $request->guest_type,
            'date'     => $calendarDate,
            'department_id'   => $request->department_id,
            'location_id'     => $request->location_id,
            'event_id'     => $request->event_id,
            'calendar_id'     => $calendarId,
            'guest_name'      => $request->guest_name,
            'guest_count'     => $request->guest_count,
            'guest_remarks'   => $request->guest_remarks,
            'attend_user_id'  => $request->attend_user_id,
            'status'          => 1,
            'created_by'      => $authUser->id,
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
            'event_id'     => 'required|exists:event_masters,id',
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

        $dayStatusId = DayStatus::whereBetween('date', [$startDate, $endDate])->where('location_id', $request->location_id)
            ->pluck('id');

        $guestList = Guest::with([
            'attendUser:id,first_name,role',
            'calendar:id,date'
        ])
            ->whereIn('calendar_id', $dayStatusId)
            ->where('attend_user_id', $request->user_id)
            ->where('location_id', $request->location_id)
            ->where('event_id', $request->event_id)
            ->latest()
            ->get()
            ->map(function ($guest) {

                if ($guest->attendUser) {
                    $guest->attendUser->date = optional($guest->calendar)->date;
                }

                unset($guest->calendar);

                return $guest;
            });

        $personalGuestCount = $guestList->where('guest_type', 'Personal Guest')->sum('guest_count');
        $officeGuestCount   = $guestList->where('guest_type', 'Office Guest')->sum('guest_count');


        return response()->json([
            'status' => true,
            'message' => 'Guest list fetched successfully.',
            'summary' => [
                'total_guest' => $guestList->sum('guest_count'),
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
            'event_id'     => 'required|exists:event_masters,id',
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
            ->where('location_id', $request->location_id)
            ->whereDate('date', $request->date)
            ->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid calendar or date.'
            ], 404);
        }

        $today = Carbon::today()->toDateString();

        $CompanyParameter = CompanyParameter::where('location_id', $request->location_id)->where('event_id', $request->event_id)->where('status', 1)->first();

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
                'event_id'     => $request->event_id,
            ],
            [
                'absent_flag' => $request->absent_flag,
                'status'      => 1,
            ]
        );

        AttendanceLog::create([
            'calendar_id' => $calendar->id,
            'user_id'     => $userData->id,
            'event_id'     => $request->event_id,
            'absent_flag' => $request->absent_flag,
            'created_by'  => auth()->id(),
            'remarks'     => 'Attendance updated',
            'status'      => 1,
        ]);

        if ($userData->multilocation_flag == 1) {
            // Get all locations for this user
            $multiLocationData = MultipleLocation::where('user_id', $userData->id)->get();

            if ($userData->location_id == $calendar->location_id) {

                if ($request->absent_flag == 0) {
                    // Loop through each location and create/update attendance
                    foreach ($multiLocationData as $location) {
                        AttendanceAbsent::updateOrCreate(
                            [
                                'calendar_id' => $calendar->id,
                                'user_id'     => $userData->id,
                                'location_id' => $location->location_id,
                                'event_id'     => $request->event_id,
                            ],
                            [
                                'absent_flag' => 1,
                                'status'      => 1,
                            ]
                        );
                    }
                }
            } else {
                // Get all locations for this user
                $location = Location::where('id', $userData->location_id)->first();

                if ($request->absent_flag == 0) {
                    AttendanceAbsent::updateOrCreate(
                        [
                            'calendar_id' => $calendar->id,
                            'user_id'     => $userData->id,
                            'location_id' => $location->id,
                            'event_id'     => $request->event_id,
                        ],
                        [
                            'absent_flag' => 1,
                            'status'      => 1,
                        ]
                    );
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Attendance marked successfully.'
        ]);
    }

 public function markAttendanceCalendar(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Normalize event_id
    |--------------------------------------------------------------------------
    */

    $eventIds = $request->input('event_id');

    // If event_id comes as JSON string: "[2,4]"
    if (is_string($eventIds)) {

        $decodedEventIds = json_decode($eventIds, true);

        if (
            json_last_error() === JSON_ERROR_NONE
            && is_array($decodedEventIds)
        ) {
            $eventIds = $decodedEventIds;
        } else {

            // If event_id comes as "2,4"
            $eventIds = array_filter(
                array_map(
                    'trim',
                    explode(',', $eventIds)
                )
            );
        }
    }

    // Make sure it is always an array
    $eventIds = is_array($eventIds)
        ? array_values($eventIds)
        : [];


    /*
    |--------------------------------------------------------------------------
    | Put normalized event IDs back into request
    |--------------------------------------------------------------------------
    */

    $request->merge([
        'event_id' => $eventIds
    ]);


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validator = Validator::make($request->all(), [

        'calendar_id' => 'required|exists:day_statuses,id',

        'user_id' => 'required|exists:users,id',

        'date' => 'required|date',

        'absent_flag' => 'required|in:0,1',

        'location_id' => 'required|exists:locations,id',

        'event_id' => 'required|array|min:1',

        'event_id.*' => 'required|integer|exists:event_masters,id',
    ]);


    if ($validator->fails()) {

        return response()->json([
            'status'  => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422);
    }


    /*
    |--------------------------------------------------------------------------
    | Unique Event IDs
    |--------------------------------------------------------------------------
    */

    $eventIds = array_values(
        array_unique(
            array_map('intval', $request->event_id)
        )
    );


    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */

    $userData = User::where('status', 1)
        ->where('id', $request->user_id)
        ->first();

    if (!$userData) {

        return response()->json([
            'status'  => false,
            'message' => 'User not found.'
        ], 404);
    }


    /*
    |--------------------------------------------------------------------------
    | Start Transaction
    |--------------------------------------------------------------------------
    */

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Check Calendar
        |--------------------------------------------------------------------------
        */

        $calendar = DayStatus::where('id', $request->calendar_id)
            ->where('location_id', $request->location_id)
            ->whereDate('date', $request->date)
            ->first();

        if (!$calendar) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Invalid calendar or date.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Today
        |--------------------------------------------------------------------------
        */

        $today = Carbon::today('Asia/Kolkata')->toDateString();


        /*
        |--------------------------------------------------------------------------
        | Get Event Names
        |--------------------------------------------------------------------------
        */

        $events = EventMaster::whereIn('id', $eventIds)
            ->get([
                'id',
                'name'
            ])
            ->keyBy('id');


        /*
        |--------------------------------------------------------------------------
        | Company Parameters
        |--------------------------------------------------------------------------
        */

        $companyParameters = CompanyParameter::where(
                'location_id',
                $request->location_id
            )
            ->whereIn('event_id', $eventIds)
            ->where('status', 1)
            ->get()
            ->keyBy('event_id');


        /*
        |--------------------------------------------------------------------------
        | Check Company Parameter For Every Event
        |--------------------------------------------------------------------------
        */

        foreach ($eventIds as $eventId) {

            if (!$companyParameters->has($eventId)) {

                DB::rollBack();

                $eventName = $events->get($eventId)?->name
                    ?? "Event ID {$eventId}";

                return response()->json([
                    'status' => false,
                    'message' => "Company Parameter not set for {$eventName}."
                ], 404);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Attendance Time Check
        |--------------------------------------------------------------------------
        */

        if ($calendar->date == $today) {

            $currentTime = Carbon::now('Asia/Kolkata');

            foreach ($eventIds as $eventId) {

                $companyParameter = $companyParameters->get($eventId);

                $maxTime = Carbon::parse(
                    $companyParameter->attendance_out_time
                );

                if (
                    $currentTime->format('H:i:s')
                    > $maxTime->format('H:i:s')
                ) {

                    $eventName = $events->get($eventId)?->name
                        ?? "Event ID {$eventId}";

                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => "Attendance cannot be marked for {$eventName} after "
                            . $maxTime->format('h:i A')
                            . ". The maximum allowed attendance marking time has been exceeded."
                    ], 422);
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Multiple Locations
        |--------------------------------------------------------------------------
        */

        $multiLocationData = collect();

        if ($userData->multilocation_flag == 1) {

            $multiLocationData = MultipleLocation::where(
                'user_id',
                $userData->id
            )->get();
        }


        /*
        |--------------------------------------------------------------------------
        | Mark Attendance Event Wise
        |--------------------------------------------------------------------------
        */

        foreach ($eventIds as $eventId) {

            /*
            |--------------------------------------------------------------------------
            | Event Name
            |--------------------------------------------------------------------------
            */

            $eventName = $events->get($eventId)?->name
                ?? "Event ID {$eventId}";


            /*
            |--------------------------------------------------------------------------
            | Main Location Attendance
            |--------------------------------------------------------------------------
            */

            AttendanceAbsent::updateOrCreate(
                [
                    'calendar_id' => $calendar->id,
                    'user_id'     => $userData->id,
                    'location_id' => $request->location_id,
                    'event_id'    => $eventId,
                ],
                [
                    'absent_flag' => $request->absent_flag,
                    'status'      => 1,
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Attendance Log
            |--------------------------------------------------------------------------
            */

            AttendanceLog::create([
                'calendar_id' => $calendar->id,
                'user_id'     => $userData->id,
                'event_id'    => $eventId,
                'absent_flag' => $request->absent_flag,
                'created_by'  => auth()->id(),
                'remarks'     => "Attendance updated for {$eventName}",
                'status'      => 1,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Multiple Location Attendance
            |--------------------------------------------------------------------------
            */

            if ($userData->multilocation_flag == 1) {

                /*
                |--------------------------------------------------------------------------
                | User's Main Location
                |--------------------------------------------------------------------------
                */

                if ($userData->location_id == $calendar->location_id) {

                    /*
                    |--------------------------------------------------------------------------
                    | Present In Main Location
                    | Mark Absent In Other Locations
                    |--------------------------------------------------------------------------
                    */

                    if ($request->absent_flag == 0) {

                        foreach ($multiLocationData as $location) {

                            /*
                            | Don't mark main location again
                            */

                            if (
                                $location->location_id
                                == $request->location_id
                            ) {
                                continue;
                            }


                            AttendanceAbsent::updateOrCreate(
                                [
                                    'calendar_id' => $calendar->id,
                                    'user_id'     => $userData->id,
                                    'location_id' => $location->location_id,
                                    'event_id'    => $eventId,
                                ],
                                [
                                    'absent_flag' => 1,
                                    'status'      => 1,
                                ]
                            );
                        }
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Calendar Is Other Location
                    |--------------------------------------------------------------------------
                    */

                    $userMainLocation = Location::find(
                        $userData->location_id
                    );

                    if (
                        $request->absent_flag == 0
                        && $userMainLocation
                    ) {

                        AttendanceAbsent::updateOrCreate(
                            [
                                'calendar_id' => $calendar->id,
                                'user_id'     => $userData->id,
                                'location_id' => $userMainLocation->id,
                                'event_id'    => $eventId,
                            ],
                            [
                                'absent_flag' => 1,
                                'status'      => 1,
                            ]
                        );
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */

        DB::commit();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status'  => true,
            'message' => 'Attendance marked successfully.',

            'data' => [

                'calendar_id' => $calendar->id,

                'user_id' => $userData->id,

                'location_id' => $request->location_id,

                'absent_flag' => (int) $request->absent_flag,

                'events' => collect($eventIds)
                    ->map(function ($eventId) use ($events) {

                        return [
                            'event_id' => (int) $eventId,

                            'event_name' => $events->get($eventId)?->name,
                        ];
                    })
                    ->values()
                    ->toArray(),
            ]
        ]);

    } catch (\Exception $e) {

        /*
        |--------------------------------------------------------------------------
        | Rollback On Error
        |--------------------------------------------------------------------------
        */

        DB::rollBack();

        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong while marking attendance.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    // Canteen Incharge 
    public function manageAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'event_id'     => 'required|exists:event_masters,id',
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
        $eventId = $request->event_id;


        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            return response()->json([
                'status' => false,
                'message' => 'Manage attendance is not available for this role.'
            ], 403);
        }

        $today = Carbon::today()->format('Y-m-d');

        $dayStatus = DayStatus::where('date', $today)->where('location_id', $request->location_id)->first();

        if (!$dayStatus) {
            return response()->json([
                'status' => false,
                'message' => 'Day status not found for today.',
            ], 404);
        }


        $eventUserIds = UserEvent::where('event_id', $eventId)->where('status', 1)
            ->pluck('user_id')
            ->toArray();

        $singleLinkedUserIds = User::where('location_id', $locationId)
            ->whereNotNull('start_calendar_id')
            ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
            ->whereIn('users.id', $eventUserIds)
            ->where('status', 1)
            ->pluck('id');

        $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
            ->where('multiple_locations.location_id', $locationId)
            ->where('users.status', 1)
            ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
            ->whereIn('users.id', $eventUserIds)
            ->pluck('multiple_locations.user_id');

        $allLinkedUserIds = $singleLinkedUserIds
            ->merge($multiLinkedUserIds)
            ->unique()
            ->values();

        $users =  User::whereIn('users.id', $allLinkedUserIds)
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->whereNotNull('users.start_calendar_id')
            ->where('users.start_calendar_id', '<=', $dayStatus->id)
            ->leftJoin('attendance_absents', function ($join) use ($dayStatus, $locationId, $eventId) {
                $join->on('users.id', '=', 'attendance_absents.user_id')
                    ->where('attendance_absents.location_id', $locationId)
                    ->where('attendance_absents.event_id', $eventId)
                    ->where('attendance_absents.calendar_id', $dayStatus->id);
            })
            // ->whereDate('day_statuses.date', '<=', Carbon::today())
            ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
            ->select(
                'users.id',
                'users.first_name',
                'users.role',
                'departments.name as department_name',
                DB::raw('COALESCE(attendance_absents.absent_flag, 0) as absent_flag'),
                DB::raw("
            CASE
                WHEN COALESCE(attendance_absents.absent_flag, 0) = 1
                THEN 'Absent'
                ELSE 'Present'
            END as attendance_status
        ")
            )
            ->orderBy('users.first_name')
            ->where('users.status', 1)
            ->get();


        $presentCount = $users->where('absent_flag', 0)->count();
        $absentCount  = $users->where('absent_flag', 1)->count();

        $calendarId = DayStatus::where('date', Carbon::today()->toDateString())->where('location_id', $request->location_id)->where('open_flag', 1)->value('id');

        if (!$calendarId) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance is closed or today\'s calendar is not available.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Attendance List',
            'data' => [
                'summary' => [
                    'total_users' => $users->count(),
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'calendarId' => $calendarId
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
            'event_id'     => 'required|exists:event_masters,id',
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

            $dayStatus = DayStatus::where('date', $request->attendance_date)->where('location_id', $locationId)->where('open_flag', 1)->first();

            if (!$dayStatus) {
                return response()->json([
                    'status' => false,
                    'message' => 'Day status not found for the selected date.',
                ], 404);
            }

            $userCheck = User::where('id', $request->user_id)->first();

            if (!$userCheck) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.',
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
                ->where('event_id', $request->event_id)
                ->first();

            $companyParameter = CompanyParameter::where('location_id', $locationId)->where('event_id', $request->event_id)->where('status', 1)->first();
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
                    'event_id'      =>  $request->event_id,
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
                'event_id'      =>  $request->event_id,
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
            'event_id'     => 'required|exists:event_masters,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locationId = $request->location_id;
        $eventId = $request->event_id;

        $authUser = Auth::user();

        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            return response()->json([
                'status' => false,
                'message' => 'Manage attendance is not available for this role.'
            ], 403);
        }

        $today = Carbon::today()->format('Y-m-d');

        $dayStatus = DayStatus::where('date', $today)->where('location_id', $locationId)->first();

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

        $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
            ->where('multiple_locations.location_id', $locationId)
            ->where('users.status', 1)
            ->pluck('multiple_locations.user_id');

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
            ->where('event_id', $eventId)

            // ->whereIn('attend_user_id', $allLinkedUserIds)
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

        $personalGuestCount = $guestList->where('guest_type', 'Personal Guest')->sum('guest_count');
        $officeGuestCount   = $guestList->where('guest_type', 'Office Guest')->sum('guest_count');

        return response()->json([
            'status' => true,
            'message' => 'Guest list fetched successfully.',
            'summary' => [
                'total_guest' => $guestList->sum('guest_count'),
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
