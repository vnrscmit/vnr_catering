<?php

namespace App\Http\Controllers\Admin;

use App\Models\CompanyParameter;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use App\Mail\PasswordChangedNotification;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Controllers\Traits\OrderStatisticsTrait;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\AttendanceAbsent;
use App\Models\DayStatus;
use Carbon\Carbon;
use App\Models\DailyMenu;
use App\Models\Guest;
use App\Models\MultipleLocation;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use OrderStatisticsTrait;
    use AdminViewSharedDataTrait;


    public function __construct()
    {
        $this->shareAdminViewData();
        $this->shareOrderStatistics();
    }

    public function index($locationId = null)
    {
        $UserData = Auth::user();
        if ($locationId === null) {
            $locationId = $UserData->location_id;
        }


        if (!$locationId) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'The selected location was not found.');
        }

        $formattedSalesData = [];
        $today = Carbon::today()->format('Y-m-d');
        $dayStatus = DayStatus::where('date', $today)->where('location_id', $locationId)->first();

        $companyParameter = CompanyParameter::where('location_id', $locationId)->where('status', 1)->first();

        if (!$companyParameter) {
            return redirect()->route('admin.dashboard')->with('error', 'Company parameter is not configured for your location.');
        }

        $allLocked = false;

        $userStartDate = DayStatus::where('id', $dayStatus->id)->value('date');
        if ($UserData->start_calendar_id == null || $userStartDate > $dayStatus->date) {
            $allLocked = true;
        }

        $currentTime = Carbon::now();

        // Today's attendance out time
        $cutoffTime = Carbon::today()->setTimeFrom($companyParameter->attendance_out_time);
        $isLocked = false;
        $remainingSeconds = 0;

        if ($currentTime->greaterThanOrEqualTo($cutoffTime)) {
            $isLocked = true;
        } else {
            $remainingSeconds = $currentTime->diffInSeconds($cutoffTime);
        }

        // $dailyMenuList = DailyMenu::with('items.submenu')
        //     ->where('calendar_id', $dayStatus->id)->where('location_id', $locationId)->where('menu_date', $today)->first();

        // $todayMenu = $dailyMenuList
        //     ? $dailyMenuList->items->pluck('submenu.name')->values()->toArray()
        //     : [];

        $dailyMenuList = DailyMenu::with('items.menu', 'items.submenu')
            ->where('calendar_id', $dayStatus->id)
            ->where('location_id', $locationId)
            ->where('menu_date', $today)
            ->first();

        $order = [
            'Starters'        => 1,
            'Refresher'       => 2,
            'Vegetable'       => 3,
            'Dal'             => 4,
            'Rice'            => 5,
            'Roti'            => 6,
            'Dessert'         => 7,
            'Accompaniments'  => 8,
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
 

        $upComingDays = DayStatus::where('day_statuses.date', '>', $today)
            ->leftJoin('attendance_absents', function ($join) use ($locationId) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', auth()->id())
                    ->where('attendance_absents.location_id', $locationId)
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
            ->limit($companyParameter->max_day_show)
            ->get();

        $todaysAttendance =  DayStatus::where('day_statuses.date', '=', $today)
            ->leftJoin('attendance_absents', function ($join) use ($locationId) {
                $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                    ->where('attendance_absents.user_id', auth()->id())
                    ->where('attendance_absents.location_id', $locationId)
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
            ->limit($companyParameter->max_day_show)
            ->first();


        $personalguestCount = 0;
        $officeguestCount   = 0;

        // Guest Calculation
        $query = Guest::where('calendar_id', $dayStatus->id)->where('location_id', $locationId);
        if ($UserData->personal_guest_flag == 1) {
            $guestAllowed = 1;
            if ($UserData->role == 'Admin' || $UserData->role == 'Super Admin') {
                $allusers = User::where('status', 1)->whereNotIn('role', ['Canteen Incharge', 'Super Admin', 'Canteen Administrator'])->get();
                $overrideLock = true;
            } elseif ($UserData->role == 'Canteen Administrator' || $UserData->role == 'Canteen Incharge') {
                $userIds = User::where('location_id', $locationId)->whereNotIn('role', ['Canteen Incharge', 'Super Admin', 'Canteen Administrator'])
                    ->pluck('id');
                // $query->whereIn('attend_user_id', $userIds);
                $allusers = User::where('status', 1)->whereNotIn('role', ['Canteen Incharge', 'Super Admin', 'Canteen Administrator'])->where('location_id', $locationId)->get();
                $overrideLock = true;
            } else {
                $query->where('attend_user_id', $UserData->id);
                $allusers  = [];
                $overrideLock = false;
            }

            $personalguestCount = (clone $query)
                ->where('guest_type', 'Personal Guest')
                ->sum('guest_count');

            $officeguestCount = (clone $query)
                ->where('guest_type', 'Office Guest')
                ->sum('guest_count');
        } else {

            $guestAllowed = 0;
            $personalguestCount = 0;
            $officeguestCount = 0;

            if ($UserData->role == 'Admin' || $UserData->role == 'Super Admin') {
                $allusers = User::where('status', 1)->whereNotIn('role', ['Canteen Incharge', 'Super Admin', 'Canteen Administrator'])->get();
                $overrideLock = true;
            } elseif ($UserData->role == 'Canteen Incharge' || $UserData->role == 'Canteen Administrator') {
                $userIds = User::where('location_id', $locationId)->whereNotIn('role', ['Canteen Incharge', 'Super Admin', 'Canteen Administrator'])
                    ->pluck('id');
                $query->whereIn('attend_user_id', $userIds);
                $allusers = User::where('status', 1)->where('location_id', $locationId)->whereNotIn('role', ['Canteen Incharge', 'Super Admin', 'Canteen Administrator'])->get();
                $overrideLock = true;
            } else {
                $query->where('attend_user_id', $UserData->id);
                $allusers  = [];
                $overrideLock = false;
            }
        }

        // Dashboard Present Absent and Other Counts

        $presentCount = 0;
        $absentCount = 0;
        $totalGuest = 0;
        $totalMeal = 0;

        $usersAttendance = [];

        if (($UserData->role == 'Canteen Incharge' || $UserData->role == 'Canteen Administrator')) {
            $singleLinkedUserIds = User::where('location_id', $locationId)
                ->whereNotNull('start_calendar_id')
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->where('status', 1)
                ->pluck('id');

            $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
                ->where('multiple_locations.location_id', $locationId)
                ->where('users.status', 1)
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->pluck('multiple_locations.user_id');

            $allLinkedUserIds = $singleLinkedUserIds
                ->merge($multiLinkedUserIds)
                ->unique()
                ->values();



            $totalUsers = $allLinkedUserIds->count();


            $absentCount = AttendanceAbsent::where('calendar_id', $dayStatus->id)
                ->where('location_id', $locationId)
                ->where('absent_flag', 1)
                ->whereIn('user_id', $allLinkedUserIds)
                ->count();


            $presentCount = $totalUsers - $absentCount;

            $officialGuestCount = Guest::where('calendar_id', $dayStatus->id)
                ->where('location_id',  $locationId)
                ->where('guest_type', 'Office Guest')
                ->sum('guest_count');

            $personalGuestCount = Guest::where('calendar_id', $dayStatus->id)
                ->where('location_id',  $locationId)
                ->where('guest_type', 'Personal Guest')
                ->sum('guest_count');


            $totalGuest = $officialGuestCount + $personalGuestCount;

            $totalMeal =  $totalGuest + $presentCount;

            $usersAttendance = User::whereIn('users.id', $allLinkedUserIds)
                ->join('departments', 'users.department_id', '=', 'departments.id')
                ->whereNotNull('users.start_calendar_id')
                ->where('users.start_calendar_id', '<=', $dayStatus->id)
                ->leftJoin('attendance_absents', function ($join) use ($dayStatus, $locationId) {
                    $join->on('users.id', '=', 'attendance_absents.user_id')
                        ->where('attendance_absents.location_id', $locationId)
                        ->where('attendance_absents.calendar_id', $dayStatus->id);
                })
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
        }


        $summaryCurrentMonth = Null;
        $allLocations = [];
        if (($UserData->role == 'Member' || $UserData->role == 'Non Member')) {

            $currentStart = Carbon::now()->startOfMonth();
            $currentEnd   = Carbon::now()->endOfMonth();

            $startDate = DayStatus::where('id', $UserData->start_calendar_id)->value('date');
            // Current Month Summary Data
            $summaryCurrentMonth = DayStatus::whereBetween('day_statuses.date', [
                $currentStart->format('Y-m-d'),
                $currentEnd->format('Y-m-d')
            ])
                ->where('day_statuses.date', '>=', $startDate)
                ->leftJoin('attendance_absents', function ($join)  use ($UserData, $locationId) {
                    $join->on('day_statuses.id', '=', 'attendance_absents.calendar_id')
                        ->where('attendance_absents.location_id', $locationId)
                        ->where('attendance_absents.user_id', $UserData->id);
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

                $summaryCurrentMonth->presentPercentage = rtrim(rtrim(number_format(($presentDays / $monthDayCount) * 100, 2), '0'), '.');

                $summaryCurrentMonth->absentPercentage = rtrim(rtrim(number_format(($summaryCurrentMonth->absent_days / $monthDayCount) * 100, 2), '0'), '.');
            } else {
                $summaryCurrentMonth->presentPercentage = 0;
                $summaryCurrentMonth->absentPercentage = 0;
            }

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
        }
     

        return view('admin.dashboard', compact(
            'formattedSalesData',
            'todayMenu',
            'upComingDays',
            'isLocked',
            'remainingSeconds',
            'UserData',
            'todaysAttendance',
            'companyParameter',
            'dayStatus',
            'overrideLock',
            'guestAllowed',
            'personalguestCount',
            'officeguestCount',
            'allusers',
            'allLocked',
            'presentCount',
            'absentCount',
            'totalGuest',
            'totalMeal',
            'usersAttendance',
            'summaryCurrentMonth',
            'allLocations'
        ));
    }


    public function viewMyProfile()
    {
        $user = Auth::User();
        return view('admin.view-my-profile', compact('user'));
    }


    public function editMyProfile()
    {
        $user = Auth::User();
        return view('admin.edit-my-profile', compact('user'));
    }

  public function updateMyProfile(UpdateProfileRequest $request)
{
    $user = Auth::User();
    $validated = $request->validated();

    $user->first_name = $validated['first_name'];
    $user->last_name = $validated['first_name'];
    $user->email = $validated['email'];
    $user->mobile = $validated['mobile'];

    // Handle profile photo upload from cropped image
    if ($request->filled('cropped_image')) {
        // Delete old profile photo if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete('profile-picture/' . $user->profile_picture);
        }

        // Get the base64 image data
        $imageData = $request->input('cropped_image');
        
        // Remove the data URL prefix (data:image/jpeg;base64,)
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        
        // Generate a unique filename
        $fileName = time() . '_' . uniqid() . '.jpg';
        
        // Decode and save the image
        $imageDecoded = base64_decode($imageData);
        
        // Store the image
        Storage::disk('public')->put('profile-picture/' . $fileName, $imageDecoded);
        
        // Update user's profile picture
        $user->profile_picture = $fileName;
    }

    // Save the updated user data
    $user->save();

    // Return success message
    return back()->with('success', 'Profile updated successfully.');
}


    public function showChangePasswordForm()
    {
        return view('admin.change-password');
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::User();

        // Check if the current password matches the user's password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Send password changed notification email
        Mail::to($user->email)->send(new PasswordChangedNotification($user));

        return redirect()->route('admin.dashboard')->with('success', 'Your password has been successfully updated.');
    }
}
