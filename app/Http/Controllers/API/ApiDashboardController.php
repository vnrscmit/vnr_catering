<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAbsent;
use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\CompanyParameter;
use App\Models\DailyMenu;
use App\Models\DayStatus;
use App\Models\Guest;
use App\Models\MultipleLocation;
use App\Models\RateMaster;
use App\Models\User;
use App\Models\UserEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'event_id' => 'required|exists:event_masters,id',
        ]);

        $user = Auth::user();
        $locationId = $request->location_id;
        $eventId = $request->event_id;

        if (in_array($user->role, ['Member', 'Non Member'])) {
            return $this->memberDashboard($user, $locationId, $eventId);
        }
        if (in_array($user->role, ['Canteen Incharge', 'Canteen Administrator'])) {
            return $this->inchargeDashboard($user,  $locationId,  $eventId);
        }

        if (in_array($user->role, ['Canteen President'])) {
            return $this->memberDashboard($user, $locationId,  $eventId);
        }

        return response()->json([
            'status' => false,
            'message' => 'Dashboard is not available for this role.'
        ], 403);
    }

    private function memberDashboard($userData, $locationId,  $eventId)
    {

        if ($userData->start_calendar_id == null) {
            return response()->json([
                'status' => false,
                'message' => 'Start date is not set please contact your canteen incharge for mark present absent.',
            ], 400);
        }

        $multilocationCount = MultipleLocation::where('user_id', $userData->id)->where('location_id', $locationId)->count();
        if (($userData->location_id != $locationId) && $multilocationCount < 1) {
            return response()->json([
                'status' => false,
                'message' => 'The selected location is not linked to your account.',
            ], 400);
        }

        $CompanyParameter = CompanyParameter::where('location_id', $locationId)->where('event_id', $eventId)->where('status', 1)->first();

        if (!$CompanyParameter) {
            return response()->json([
                'status' => false,
                'message' => 'Company parameter is not configured for your location.',
            ], 400);
        }

        $today = Carbon::today()->format('Y-m-d');
        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd   = Carbon::now()->endOfMonth();

        $previousStart = Carbon::now()->subMonth()->startOfMonth();
        $previousEnd   = Carbon::now()->subMonth()->endOfMonth();

        $dayStatus = DayStatus::where('date', $today)->where('location_id', $locationId)->first();

        $todaysAttendance = DayStatus::where('day_statuses.date', $today)
            ->leftJoin('attendance_absents', function ($join) use ($locationId, $eventId) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', auth()->id())
                    ->where('attendance_absents.location_id', $locationId)
                    ->where('attendance_absents.event_id', $eventId)
                    ->where('attendance_absents.absent_flag', 1);
            })
            ->select(
                'day_statuses.*',
                DB::raw('COALESCE(attendance_absents.absent_flag, 0) as absent_flag')
            )
            ->where('day_statuses.sunday_flag', 0)
            ->where('day_statuses.holiday_flag', 0)
            ->where('day_statuses.open_flag', 1)
            ->where('day_statuses.location_id', $locationId)
            ->orderBy('day_statuses.date', 'asc')
            ->limit($CompanyParameter->max_day_show)
            ->get();


        $checkDate = DayStatus::where('id', $userData->start_calendar_id)->value('date');

        $startDate  = DayStatus::where('date', $checkDate)->where('location_id', $locationId)->value('date');

        $upComingDays = DayStatus::where('day_statuses.date', '>', $today)
            ->where('day_statuses.date', '>=', $startDate)
            ->leftJoin('attendance_absents', function ($join) use ($locationId, $eventId) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', auth()->id())
                    ->where('attendance_absents.location_id', $locationId)
                    ->where('attendance_absents.event_id', $eventId)
                    ->where('attendance_absents.absent_flag', 1);
            })
            ->select(
                'day_statuses.*',
                DB::raw('COALESCE(attendance_absents.absent_flag, 0) as absent_flag')
            )
            ->where('day_statuses.sunday_flag', 0)
            ->where('day_statuses.holiday_flag', 0)
            ->where('day_statuses.open_flag', 1)
            ->where('day_statuses.location_id', $locationId)
            ->orderBy('day_statuses.date', 'asc')
            ->limit($CompanyParameter->max_day_show)
            ->get();

        $checkDate = DayStatus::where('id', $userData->start_calendar_id)->value('date');

        $startDate  = DayStatus::where('date', $checkDate)->where('location_id', $locationId)->value('date');



        // Current Month Summary Data
        $summaryCurrentMonth = DayStatus::whereBetween('day_statuses.date', [
            $currentStart->format('Y-m-d'),
            $currentEnd->format('Y-m-d')
        ])
            ->where('day_statuses.date', '>=', $startDate)
            ->leftJoin('attendance_absents', function ($join)  use ($userData, $locationId, $eventId) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.location_id', $locationId)
                    ->where('attendance_absents.event_id', $eventId)
                    ->where('attendance_absents.user_id', $userData->id);
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


        $summaryCurrentMonth->totalDays = $monthDayCount;
        $presentDays = ($monthDayCount - $summaryCurrentMonth->absent_days);
        $summaryCurrentMonth->presentDays = $presentDays;
        $summaryCurrentMonth->totalPercentage = 100;

        if ($monthDayCount > 0) {
            $summaryCurrentMonth->presentPercentage = number_format(($presentDays / $monthDayCount) * 100, 2);
            $summaryCurrentMonth->absentPercentage  = number_format(($summaryCurrentMonth->absent_days / $monthDayCount) * 100, 2);
        } else {
            $summaryCurrentMonth->presentPercentage = 0;
            $summaryCurrentMonth->absentPercentage  = 0;
        }

        // Previous Month Summary Data


        // Previous Month Summary Data

        $billId = Bill::where('location_id', $locationId)
            ->where('generate_month', $previousStart->format('Y-m'))
            ->value('id');

        $billDetails = BillDetail::where('bill_id', $billId)
            ->where('user_id', $userData->id)
            ->first();

        $guestCount = Guest::where('attend_user_id', $userData->id)
            ->where('location_id', $locationId)
            ->whereBetween('date', [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')])
            ->sum('guest_count');


            $guestRatePerDiet = RateMaster::where('location_id', $locationId)
            ->where('effective_from_date', $previousStart->format('Y-m-d'))
            ->where('status', 1)
            ->value('guest_rate');

        if ($billDetails) {
            $summaryMealCount = [
                'your_meal_count' => $billDetails->user_diets,
                'your_meal_rate' => $billDetails->rate_per_diet,
                'your_meal_amount' => $billDetails->bill_amount,
                'pre_balance' => $billDetails->pre_balance,
                'balance' => $billDetails->pre_balance,
                'payment_flag' => $billDetails->payment_flag,
                'guest_meal_count' => $guestCount,
                'guest_meal_rate' => $guestRatePerDiet,
                'guest_amount' => $guestCount * $guestRatePerDiet,
                'total_amount' => ( ($guestCount * $guestRatePerDiet) + ($billDetails->bill_amount)),
            ];
        } else {
             $summaryMealCount = [
        'your_meal_count'  => 0,
        'your_meal_rate'   => 0,
        'your_meal_amount' => 0,
        'pre_balance'      => 0,
        'balance'          => 0,
        'payment_flag'     => 0,

        'guest_meal_count' => $guestCount,
        'guest_meal_rate'  => $guestRatePerDiet,
        'guest_amount'     => $guestCount * $guestRatePerDiet,

        'total_amount'     => $guestCount * $guestRatePerDiet,
    ];
        }
        //End of Previous Month Summary Data

        $personalguestCount = 0;
        $officeguestCount = 0;

        if ($userData->personal_guest_flag == 1) {
            $guestAllowed = 1;
            $personalguestCount = Guest::where('attend_user_id', $userData->id)
                ->where('location_id', $locationId)
                ->where('calendar_id', $dayStatus->id)
                ->where('event_id', $eventId)
                ->where('guest_type', 'Personal Guest')
                ->sum('guest_count');

            $officeguestCount = Guest::where('attend_user_id', $userData->id)
                ->where('location_id', $locationId)
                ->where('calendar_id', $dayStatus->id)
                ->where('event_id', $eventId)
                ->where('guest_type', 'Office Guest')
                ->sum('guest_count');
        } else {
            $guestAllowed = 0;
            $personalguestCount = 0;
            $officeguestCount = 0;
        }

        // Daily Menu List

        // $dailyMenuList = DailyMenu::with('items.submenu')
        //     ->where('calendar_id', $dayStatus->id)->where('status', 1)->where('menu_date', $today)->where('location_id', $locationId)->first();

        // $todayMenu = $dailyMenuList
        //     ? $dailyMenuList->items->pluck('submenu.name')->values()->toArray()
        //     : [];

        $dailyMenuList = DailyMenu::with('items.menu', 'items.submenu')
            ->where('calendar_id', $dayStatus->id)
            ->where('location_id', $locationId)
            ->where('event_id', $eventId)
            ->where('menu_date', $today)
            ->first();

        $order = [
            'Starters'        => 1,
            'Refresher'       => 2,
            'Vegetable'       => 3,
            'Dal'             => 4,
            'Roti'            => 5,
            'Rice'            => 6,
            'Accompaniments'  => 7,
            'Dessert'         => 8,

        ];

        $todayMenu = $dailyMenuList
            ? $dailyMenuList->items
            ->sortBy(function ($item) use ($order) {
                return $order[$item->menu->name] ?? 999;
            })
            ->map(function ($item) {
                return [
                    'name' => $item->submenu->name ?? '',
                    'special_flag' => $item->submenu->special_flag ?? 0
                ];
            })
            ->values()
            ->toArray()
            : [];

        $startDate = Carbon::parse(
            DayStatus::where('id', $userData->start_calendar_id)->value('date')
        )->format('d-m-Y');

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
            'summaryMealCount' => $summaryMealCount,
            'attendance_out_time' => Carbon::parse($CompanyParameter->attendance_out_time)->format('h:i A'),

            'lunch_out_time' => $CompanyParameter->lunch_out_time
                ? Carbon::parse($CompanyParameter->lunch_out_time)->format('h:i A')
                : null,

            'canteen_start_time' => Carbon::parse($CompanyParameter->canteen_start_time)->format('h:i A'),

            'canteen_end_time' => $CompanyParameter->canteen_end_time
                ? Carbon::parse($CompanyParameter->canteen_end_time)->format('h:i A')
                : null,

            'startDate' => $startDate ?? '',
        ];


        return response()->json([
            'status' => true,
            'message' => 'Dashboard Data',
            'data' => $data,
        ]);
    }

    private function inchargeDashboard($userData, $locationId,  $eventId)
    {

        $companyParameter = CompanyParameter::where('location_id', $userData->location_id)->where('event_id', $eventId)->where('status', 1)->first();


        if (!$companyParameter) {
            return response()->json([
                'status' => false,
                'message' => 'Company parameter is not configured for your location.',
            ], 400);
        }

        $today = Carbon::today()->format('Y-m-d');

        $dayStatus = DayStatus::where('date', $today)->where('location_id', $userData->location_id)->first();

        if (!$dayStatus) {
            return response()->json([
                'status' => false,
                'message' => 'Day status not found for today.',
            ], 400);
        }

        $eventUserIds = UserEvent::where('event_id', $eventId)->where('status', 1)
            ->pluck('user_id')
            ->toArray();

        $singleLinkedUserIds = User::where('location_id', $userData->location_id)
            ->whereNotNull('start_calendar_id')
            ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
            ->whereIn('users.id', $eventUserIds)
            ->where('status', 1)
            ->pluck('id');


        $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
            ->where('multiple_locations.location_id', $userData->location_id)
            ->where('users.status', 1)
            ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
            ->whereIn('users.id', $eventUserIds)
            ->pluck('multiple_locations.user_id');

        $allLinkedUserIds = $singleLinkedUserIds
            ->merge($multiLinkedUserIds)
            ->unique()
            ->values();

        $totalUsers = $allLinkedUserIds->count();

        $absentCount = AttendanceAbsent::where('calendar_id', $dayStatus->id)
            ->where('location_id', $userData->location_id)
            ->where('absent_flag', 1)
            ->whereIn('user_id', $allLinkedUserIds)
            ->where('event_id', $eventId)
            ->count();

        $presentCount = $totalUsers - $absentCount;

        $officialGuestCount = Guest::where('calendar_id', $dayStatus->id)
            ->where('location_id', $userData->location_id)
            ->where('event_id', $eventId)
            ->where('guest_type', 'Office Guest')
            ->sum('guest_count');

        $personalGuestCount = Guest::where('calendar_id', $dayStatus->id)
            ->where('location_id', $userData->location_id)
            ->where('event_id', $eventId)
            ->where('guest_type', 'Personal Guest')
            ->sum('guest_count');

        $lateChangesUserId = AttendanceAbsent::where('late_flag', 1)->where('calendar_id', $dayStatus->id)->where('event_id', $eventId)->pluck('user_id')->toArray();

        $presentToAbsent = User::where('status', 1)->where('role', 'Member')->whereIn('id', $lateChangesUserId)
            ->count();

        $absentToPresent = User::where('status', 1)->where('role', 'Non Member')->whereIn('id', $lateChangesUserId)
            ->count();

        $lateGuest = Guest::where('late_flag', 1)->where('calendar_id', $dayStatus->id)->where('event_id', $lateChangesUserId)->sum('guest_count');

        $lateEntry = [
            'presentToAbsent' => $presentToAbsent,
            'absentToPresent' => $absentToPresent,
            'lateGuest' => $lateGuest,
        ];

        if ($userData->personal_guest_flag == 1) {
            $guestAllowed = 1;
        } else {
            $guestAllowed = 0;
        }

        $data = [
            'total_users'          => $totalUsers,
            'present_count'        => $presentCount,
            'absent_count'         => $absentCount,
            'official_guest_count' => $officialGuestCount,
            'personal_guest_count' => $personalGuestCount,
            'total_meals'          => $presentCount + $officialGuestCount + $personalGuestCount,
            'lateEntry'           => $lateEntry,
            'attendance_out_time' => Carbon::parse($companyParameter->attendance_out_time)->format('h:i A'),

            'lunch_out_time' => $companyParameter->lunch_out_time
                ? Carbon::parse($companyParameter->lunch_out_time)->format('h:i A')
                : null,

            'canteen_start_time' => Carbon::parse($companyParameter->canteen_start_time)->format('h:i A'),

            'canteen_end_time' => $companyParameter->canteen_end_time
                ? Carbon::parse($companyParameter->canteen_end_time)->format('h:i A')
                : null,

            'startDate' => '',
            'guestAllowed' => $guestAllowed,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Dashboard Data',
            'data' => $data,
        ]);
    }
}
