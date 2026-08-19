<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DayStatus;
use App\Models\UserLocation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiReportController extends Controller
{
    public function reportAttendance(Request $request)
    {
        $userData = Auth::user();


        // Check if user has permission (only Member and Non Member can access)
        if (!in_array($userData->role, ['Member', 'Non Member'])) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to access this report.'
            ], 403);
        }

        // Validate user_id and location_id with custom error messages
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'location_id' => 'required|integer|exists:locations,id',
        ], [
            // Custom error messages
            'user_id.required' => 'User ID is required.',
            'user_id.integer' => 'User ID must be a valid integer.',
            'user_id.exists' => 'The selected user does not exist in our system.',

            'location_id.required' => 'Location ID is required.',
            'location_id.integer' => 'Location ID must be a valid integer.',
            'location_id.exists' => 'The selected location does not exist in our system.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Security check - Members can only view their own attendance
        if ($request->user_id != $userData->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only view your own attendance report.'
            ], 403);
        }

        $checklocation = UserLocation::where('user_id', $request->user_id)
            ->where('location_id', $request->location_id)
            ->first();

        if (!$checklocation) {
            return response()->json([
                'status' => false,
                'message' => 'You are not assigned to the selected location.'
            ], 403);
        }

        if ($request->user_id != $userData->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only view your own attendance report.'
            ], 403);
        }

        $userId = $request->user_id;
        $locationId = $request->location_id;
        $today = Carbon::today()->toDateString();

        try {
            // Get current year
            $currentYear = Carbon::now()->year;

            // Get current month (August = 8)
            $currentMonth = Carbon::now()->month;

            $userStartDate = DayStatus::where('id', $userData->start_calendar_id)->value('date');

            // Get month-wise attendance data from January to current month
            $monthlyAttendance = DayStatus::whereYear('day_statuses.date', $currentYear)
                ->whereMonth('day_statuses.date', '<=', $currentMonth)
                ->whereDate('day_statuses.date', '>=', $userStartDate)
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
                ->selectRaw("
                MONTH(day_statuses.date) as month,
                YEAR(day_statuses.date) as year,
                COUNT(DISTINCT day_statuses.id) as total_days,
                SUM(CASE WHEN attendance_absents.absent_flag = 1 THEN 1 ELSE 0 END) as absent_days,
                (COUNT(DISTINCT day_statuses.id) - SUM(CASE WHEN attendance_absents.absent_flag = 1 THEN 1 ELSE 0 END)) as present_days
            ")
                ->groupByRaw("YEAR(day_statuses.date), MONTH(day_statuses.date)")
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            // Calculate totals
            $totalDays = $monthlyAttendance->sum('total_days');
            $totalAbsent = $monthlyAttendance->sum('absent_days');
            $totalPresent = $monthlyAttendance->sum('present_days');

            // Get month names
            $monthNames = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December'
            ];

            // Format the response with month names
            $formattedData = $monthlyAttendance->map(function ($item) use ($monthNames) {
                return [
                    'month' => $monthNames[$item->month] ?? 'Unknown',
                    'present_days' => $item->present_days,
                ];
            });


            // ============ WEEK SUMMARY FOR CURRENT MONTH ============
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();

            // Get all days for current month
            $currentMonthDays = DayStatus::whereBetween('day_statuses.date', [
                $currentMonthStart->toDateString(),
                $currentMonthEnd->toDateString()
            ])
                ->leftJoin('attendance_absents', function ($join) use ($userId, $locationId) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.user_id', $userId)
                        ->where('attendance_absents.location_id', $locationId);
                })
                ->leftJoin('holiday_lists', function ($join) use ($locationId) {
                    $join->on('day_statuses.id', '=', 'holiday_lists.calendar_id')
                        ->on('day_statuses.location_id', '=', 'holiday_lists.location_id')
                        ->where('holiday_lists.status', 1);
                })
                ->select(
                    'day_statuses.*',
                    DB::raw('IFNULL(attendance_absents.absent_flag, 0) as absent_flag'),
                    'holiday_lists.remarks as holiday_remarks'
                )
                ->where('day_statuses.location_id', $locationId)
                ->orderBy('day_statuses.date')
                ->get();

            // Group days by week (Monday to Sunday)
            $weeks = [];
            $currentWeek = [];
            $weekStartDate = null;

            foreach ($currentMonthDays as $day) {
                $dayDate = Carbon::parse($day->date);

                // Check if it's Monday or start of new week
                if ($dayDate->dayOfWeek == Carbon::MONDAY || $weekStartDate === null) {
                    if (!empty($currentWeek)) {
                        // Calculate week summary for previous week
                        $weekDays = collect($currentWeek)->where('open_flag', 1)->values();

                        if ($weekDays->isNotEmpty()) {
                            $weekSummary = [
                                'start_date' => Carbon::parse($weekStartDate)->format('F j'),
                                'end_date' => Carbon::parse($weekDays->last()->date)->format('F j'),
                                'days' => $weekDays->count(),
                                'present' => $weekDays->filter(function ($d) use ($today) {
                                    return $d->absent_flag == 0
                                        && $d->open_flag == 1
                                        && Carbon::parse($d->date)->lte($today);
                                })->count(),
                                'absent' => $weekDays->filter(function ($d) use ($userData, $today) {
                                    return $d->absent_flag == 1
                                        && $d->open_flag == 1
                                        && $d->id >= $userData->start_calendar_id
                                        && Carbon::parse($d->date)->lte($today);
                                })->count(),
                            ];
                            $weeks[] = $weekSummary;
                        }
                    }
                    $currentWeek = [];
                    $weekStartDate = $dayDate;
                }
                $currentWeek[] = $day;
            }

            // Add last week
            if (!empty($currentWeek)) {
                $weekDays = collect($currentWeek)->where('open_flag', 1)->values();

                if ($weekDays->isNotEmpty()) {
                    $weekSummary = [
                        'start_date' => Carbon::parse($weekStartDate)->format('F j'),
                        'end_date' => Carbon::parse($weekDays->last()->date)->format('F j'),
                        'days' => $weekDays->count(),
                        'present' => $weekDays->filter(function ($d) use ($today) {
                            return $d->absent_flag == 0
                                && $d->open_flag == 1
                                && Carbon::parse($d->date)->lte($today);
                        })->count(),
                        'absent' => $weekDays->filter(function ($d) use ($userData, $today) {
                            return $d->absent_flag == 1
                                && $d->open_flag == 1
                                && $d->id >= $userData->start_calendar_id
                                && Carbon::parse($d->date)->lte($today);
                        })->count(),
                    ];
                    $weeks[] = $weekSummary;
                }
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'monthly_summary' => $formattedData,
                    'total_summary' => [
                        'total_days' => $totalDays,
                        'total_absent' => $totalAbsent,
                        'total_present' => $totalPresent,
                        'overall_percentage' => $totalDays > 0
                            ? round(($totalPresent / $totalDays) * 100, 2)
                            : 0
                    ],
                    'current_month' => $monthNames[$currentMonth],
                    'year' => $currentYear,
                    'weeks' => $weeks
                ],
                'message' => 'Attendance report fetched successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch attendance report: ' . $e->getMessage()
            ], 500);
        }
    }
}
