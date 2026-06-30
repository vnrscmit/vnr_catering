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


    public function calendar()
    {
        $userData = Auth::user();
        $previousStart = Carbon::now()->subMonth()->startOfMonth();
        $previousEnd   = Carbon::now()->subMonth()->endOfMonth();

        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd   = Carbon::now()->endOfMonth();

        $nextStart = Carbon::now()->addMonth()->startOfMonth();
        $nextEnd   = Carbon::now()->addMonth()->endOfMonth();

        $query = function ($start, $end) use ($userData) {
            return DayStatus::whereBetween('day_statuses.date', [$start, $end])
                ->leftJoin('attendance_absents', function ($join)  use ($userData) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.user_id', $userData->id);
                })
                ->select(
                    'day_statuses.*',
                    DB::raw('COALESCE(attendance_absents.absent_flag, 0) as absent_flag')
                )
                ->orderBy('day_statuses.date')
                ->get();
        };

        $previousMonth = $query($previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'));
        $currentMonth  = $query($currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'));
        $nextMonth     = $query($nextStart->format('Y-m-d'), $nextEnd->format('Y-m-d'));

        return response()->json([
            'status' => true,
            'data' => [
                'previous_month' => $previousMonth,
                'current_month'  => $currentMonth,
                'next_month'     => $nextMonth,
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $today = Carbon::today()->format('Y-m-d');
        $calendarId = DayStatus::where('date', $today)->where('open_flag', 1)->value('id');
        if ($calendarId) {
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Day Status not found.',
            ], 201);
        }

        $userData = User::findOrFail($request->attend_user_id);

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
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Guest created successfully.',
            'data' => $guest,
        ], 201);
    }


    public function markAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'calendar_id' => 'required|exists:day_statuses,id',
            'user_id' => 'required|exists:users,id',
            'date'        => 'required|date',
            'absent_flag' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userData = User::where('status', 1)->first();
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

        $CompanyParameter = CompanyParameter::where('location_id', $userData->location_id)->first();
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
        AttendanceAbsent::updateOrCreate(
            [
                'calendar_id' => $calendar->id,
                'user_id'     => $userData->id,
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
}
