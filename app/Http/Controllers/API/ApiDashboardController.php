<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CompanyParameter;
use App\Models\DailyMenu;
use App\Models\DayStatus;
use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiDashboardController extends Controller
{
    public function dashboard()
    {
        $userData = Auth::user();

        $CompanyParameter = CompanyParameter::where('location_id', $userData->location_id)->first();
        if (!$CompanyParameter) {
            return response()->json([
                'status' => false,
                'message' => 'Company parameter is not configured for your location.',
            ], 400);
        }
        $today = Carbon::today()->format('Y-m-d');
        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd   = Carbon::now()->endOfMonth();

        $dayStatus = DayStatus::where('date', $today)->first();

        $todaysAttendance =  DayStatus::where('day_statuses.date', '=', $today)
            ->leftJoin('attendance_absents', function ($join) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', auth()->id())
                    ->where('attendance_absents.absent_flag', 1);
            })
            ->select(
                'day_statuses.*',
                DB::raw('COALESCE(attendance_absents.absent_flag, 0) as absent_flag')
            )
            ->where('day_statuses.sunday_flag', 0)
            ->where('day_statuses.holiday_flag', 0)
            ->where('day_statuses.open_flag', 1)
            ->orderBy('day_statuses.date', 'asc')
            ->limit($CompanyParameter->max_day_show)
            ->get();

        $upComingDays = DayStatus::where('day_statuses.date', '>', $today)
            ->leftJoin('attendance_absents', function ($join) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', auth()->id())
                    ->where('attendance_absents.absent_flag', 1);
            })
            ->select(
                'day_statuses.*',
                DB::raw('COALESCE(attendance_absents.absent_flag, 0) as absent_flag')
            )
            ->where('day_statuses.sunday_flag', 0)
            ->where('day_statuses.holiday_flag', 0)
            ->where('day_statuses.open_flag', 1)
            ->orderBy('day_statuses.date', 'asc')
            ->limit($CompanyParameter->max_day_show)
            ->get();

        $summaryCurrentMonth = DayStatus::whereBetween('day_statuses.date', [
            $currentStart->format('Y-m-d'),
            $currentEnd->format('Y-m-d')
        ])
            ->leftJoin('attendance_absents', function ($join)  use ($userData) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', $userData->id);
            })
            ->where('day_statuses.sunday_flag', 0)
            ->where('day_statuses.holiday_flag', 0)
            ->where('day_statuses.open_flag', 1)
            ->selectRaw("
        SUM(CASE WHEN attendance_absents.absent_flag = 1 THEN 1 ELSE 0 END) as absent_days
    ")
            ->first();

        $monthDayCount = DayStatus::whereBetween('day_statuses.date', [
            $currentStart->format('Y-m-d'),
            $currentEnd->format('Y-m-d')
        ])->where('day_statuses.sunday_flag', 0)
            ->where('day_statuses.holiday_flag', 0)
            ->where('day_statuses.open_flag', 1)->count();

        $presentDays = ($monthDayCount - $summaryCurrentMonth->absent_days);
        $summaryCurrentMonth->presentDays = $presentDays;

        $personalguestCount = 0;
        $officeguestCount = 0;

        if ($userData->personal_guest_flag == 1) {
            $guestAllowed = 1;
            $personalguestCount = Guest::where('attend_user_id', $userData->id)
                ->where('guest_type', 'Personal Guest')
                ->count();

            $officeguestCount = Guest::where('attend_user_id', $userData->id)
                ->where('guest_type', 'Office Guest')
                ->count();
        } else {
            $guestAllowed = 0;
            $personalguestCount = 0;
            $officeguestCount = 0;
        }

        // Daily Menu List

        $dailyMenuList = DailyMenu::with('items.submenu')
            ->where('calendar_id', $dayStatus->id)->where('menu_date', $today)->first();

       $todayMenu = $dailyMenuList
    ? $dailyMenuList->items->pluck('submenu.name')->values()->toArray()
    : [];

        $data = [
            'today' => [
                'date' => $today,
            ],
            'upcoming_days' => $upComingDays,
            'todaysAttendance' => $todaysAttendance,
            'guestAllowed' => $guestAllowed,
            'guest_count' => [
                'personal_guest' => $personalguestCount,
                'personal_guest_allowed' => $userData->max_personal_guest_allowed,
                'office_guest' => $officeguestCount,
                'office_guest_allowed' => $userData->max_office_guest_allowed,
                'total_guest' => $personalguestCount + $officeguestCount,
            ],
            'user' => [
                'id' => $userData->id,
                'name' => $userData->first_name,
                'location_id' => $userData->location_id,
                'personal_guest_flag' => $userData->personal_guest_flag,
            ],
            'summaryCurrentMonth' => $summaryCurrentMonth,
            'todayMenus'    => $todayMenu,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Dashboard Data',
            'data' => $data,
        ]);
    }
}
