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
use App\Models\DayStatus;
use Carbon\Carbon;
use App\Models\DailyMenu;
use App\Models\Guest;
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

        if ($UserData->start_calendar_id == null || $UserData->start_calendar_id > $dayStatus->id) {
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

        $dailyMenuList = DailyMenu::with('items.submenu')
            ->where('calendar_id', $dayStatus->id)->where('location_id', $locationId)->where('menu_date', $today)->first();

        $todayMenu = $dailyMenuList
            ? $dailyMenuList->items->pluck('submenu.name')->values()->toArray()
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

        $query = Guest::where('calendar_id', $dayStatus->id)->where('location_id', $locationId);
        if ($UserData->personal_guest_flag == 1) {
            $guestAllowed = 1;
            if ($UserData->role == 'Admin' || $UserData->role == 'Super Admin') {
                $allusers = User::where('status', 1)->get();
                $overrideLock = true;
            } 
            
            elseif ($UserData->president_flag == 1 || $UserData->role == 'Canteen Incharge') {
                $userIds = User::where('location_id', $locationId)
                    ->pluck('id');
                // $query->whereIn('attend_user_id', $userIds);
                $allusers = User::where('status', 1)->where('location_id', $locationId)->get();
                $overrideLock = true;
            }
            
            else {
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
                $allusers = User::where('status', 1)->get();
                $overrideLock = true;
            } elseif ($UserData->role == 'Canteen Incharge' || $UserData->president_flag == 1) {
                $userIds = User::where('location_id', $locationId)
                    ->pluck('id');
                $query->whereIn('attend_user_id', $userIds);
                $allusers = User::where('status', 1)->where('location_id', $locationId)->get();
                $overrideLock = true;
            } else {
                $query->where('attend_user_id', $UserData->id);
                $allusers  = [];
                $overrideLock = false;
            }
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
            'todayMenu'
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
        $user->last_name =  $validated['first_name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'];

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old profile photo if exists
            if ($user->profile_picture) {
                Storage::delete('profile-picture/' . $user->profile_picture);
            }

            // Store new profile photo
            $photoPath = $request->file('profile_photo')->store('profile-picture', 'public');
            $user->profile_picture = basename($photoPath);
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
