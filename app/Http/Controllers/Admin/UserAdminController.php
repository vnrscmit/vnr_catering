<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Mail\NewAccountNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Controllers\Traits\AdminViewSharedDataTrait;
use App\Models\AttendanceAbsent;
use App\Models\DayStatus;
use App\Models\Department;
use App\Models\Location;
use App\Models\MultipleLocation;
use App\Models\RoleMaster;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Mail\UserCredentialsMail;

class UserAdminController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    // Show the admin management page
    public function index()
    {
        // Get all users except the logged-in user
        $users = User::leftJoin('day_statuses', 'users.start_calendar_id', '=', 'day_statuses.id')
            ->orderBy('users.first_name', 'asc')
            ->select(
                'users.*',
                'day_statuses.date as start_date'
            )
            ->get();
        return view('admin.users.index', compact('users'));
    }
    public function create()
    {
        // Fetch and order data for dropdowns
        $roles = RoleMaster::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        $departments = Department::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        $locations = Location::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.users.create', compact('roles', 'departments', 'locations'));
    }


    public function store(CreateUserRequest $request)
    {
        DB::beginTransaction();

        try {

            $role = RoleMaster::findOrFail($request->role_id);

            $personalGuestFlag = (int) $request->personal_guest_flag;

            $maxPersonalGuestAllowed = $personalGuestFlag
                ? (int) $request->max_personal_guest_allowed
                : 0;

            $maxOfficeGuestAllowed = $personalGuestFlag
                ? (int) $request->max_office_guest_allowed
                : 0;

            $user = User::create([
                'role_id'                    => $request->role_id,
                'role'                       => $role->name,
                'first_name'                 => $request->first_name,
                'last_name'                  => $request->first_name,
                'email'                      => $request->email,
                'mobile'                     => $request->mobile,
                'designation'                => $request->designation,
                'department_id'              => $request->department_id,
                'location_id'                => $request->location_id,
                'personal_guest_flag'        => $personalGuestFlag,
                'max_personal_guest_allowed' => $maxPersonalGuestAllowed,
                'max_office_guest_allowed'   => $maxOfficeGuestAllowed,
                'password'                   => Hash::make($request->password),
                'status'                     => $request->status,
                'notice'                     => 'Account created successfully',
                'activation_token'           => Str::random(60),
            ]);

            // Primary Location
            UserLocation::firstOrCreate(
                [
                    'user_id'       => $user->id,
                    'location_id'   => $request->location_id,
                    'department_id' => $request->department_id,
                ],
                [
                    'status' => 1,
                ]
            );

            // Additional Locations
            if ($request->filled('other_location_id')) {

                foreach ($request->other_location_id as $locationId) {

                    // Skip if same as primary location
                    if ($locationId == $request->location_id) {
                        continue;
                    }

                    UserLocation::firstOrCreate(
                        [
                            'user_id'       => $user->id,
                            'location_id'   => $locationId,
                            'department_id' => $request->department_id,
                        ],
                        [
                            'status' => 1,
                        ]
                    );

                    MultipleLocation::firstOrCreate(
                        [
                            'user_id'       => $user->id,
                            'location_id'   => $locationId,
                            'department_id' => $request->department_id,
                        ],
                        [
                            'status' => 1,
                        ]
                    );
                }

                $user->update([
                    'multilocation_flag' => 1
                ]);
            }

            $plainPassword = $request->password;
            Mail::to($user->email)->send(
                new UserCredentialsMail($user, $plainPassword)
            );


            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (Exception $e) {

            DB::rollBack();

            \Log::error('User Creation Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->except(['password', 'password_confirmation']),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', app()->environment('local')
                    ? $e->getMessage()
                    : 'Something went wrong while creating the user. Please try again.');
        }
    }


    // Update an admin
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->notice != "change_password_to_activate_account") {

            // Determine ban status and set fields accordingly
            $isBanned = $request->has('ban') && $request->ban === 'on';
            $status = $isBanned ? 0 : 1;
            $notice = $isBanned ? "banned" : null;
        } else {
            $status = $user->status;
            $notice = $user->notice;
        }

        // Update the user
        $user->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $status,
            'notice' => $notice,
        ]);

        return back()->with('success', 'User updated successfully.');
    }


    // Delete an admin
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function get_user_list()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return response()->json($users);
    }

    public function updateDate(Request $request)
    {
        $request->validate([
            'date'    => 'required|date|after_or_equal:today',
            'user_id' => 'required|exists:users,id',
        ]);

        DB::beginTransaction();

        try {


            $user = User::findOrFail($request->user_id);

            $dayStatus = DayStatus::where('date', $request->date)->where('location_id', $user->location_id)->first();

            if (!$dayStatus) {
                return redirect()->back()->with('error', 'Selected date is not available.');
            }

            $user->start_calendar_id = $dayStatus->id;
            $user->save();

            $yearDate =  DayStatus::where('id', '>=', $dayStatus->id)->where('open_flag', 1)->where('location_id', $user->location_id)->get();
            if (in_array($user->role, ['Non Member'])) {
                if ($user->role === 'Non Member') {

                    $dayStatusesOld = DayStatus::where('id', '<', $user->start_calendar_id)->where('location_id', $user->location_id)
                        ->get();

                    foreach ($dayStatusesOld as $dayStatusesOldData) {

                        AttendanceAbsent::firstOrCreate(
                            [
                                'calendar_id' => $dayStatusesOldData->id,
                                'user_id'     => $user->id,
                                'location_id'     => $user->location_id,
                            ],
                            [
                                'absent_flag'      => 1,
                                'absent_remarks'   => null,
                                'override_flag'    => 0,
                                'override_remarks' => null,
                                'status'           => 1,
                            ]
                        );
                    }


                    $dayStatuses = DayStatus::where('id', '>=', $user->start_calendar_id)->where('location_id', $user->location_id)
                        ->get();

                    foreach ($dayStatuses as $dayStatus) {
                        AttendanceAbsent::firstOrCreate(
                            [
                                'calendar_id' => $dayStatus->id,
                                'user_id'     => $user->id,
                                'location_id'     => $user->location_id,
                            ],
                            [
                                'absent_flag'      => 1,
                                'absent_remarks'   => null,
                                'override_flag'    => 0,
                                'override_remarks' => null,
                                'status'           => 1,
                            ]
                        );
                    }
                }
            } else {

                $dayStatusesOld = DayStatus::where('id', '<', $user->start_calendar_id)->where('location_id', $user->location_id)
                    ->get();

                foreach ($dayStatusesOld as $dayStatusesOldData) {

                    AttendanceAbsent::firstOrCreate(
                        [
                            'calendar_id' => $dayStatusesOldData->id,
                            'user_id'     => $user->id,
                            'location_id'     => $user->location_id,
                        ],
                        [
                            'absent_flag'      => 1,
                            'absent_remarks'   => null,
                            'override_flag'    => 0,
                            'override_remarks' => null,
                            'status'           => 1,
                        ]
                    );
                }


                $dayStatuses = DayStatus::where('id', '>=', $user->start_calendar_id)->where('location_id', $user->location_id)
                    ->where(function ($query) {
                        $query->where('open_flag', 0)
                            ->orWhere('sunday_flag', 1)
                            ->orWhere('holiday_flag', 1)
                            ->orWhere('closed_flag', 1);
                    })
                    ->get();

                foreach ($dayStatuses as $dayStatus) {

                    AttendanceAbsent::firstOrCreate(
                        [
                            'calendar_id' => $dayStatus->id,
                            'user_id'     => $user->id,
                            'location_id'     => $user->location_id,
                        ],
                        [
                            'absent_flag'      => 1,
                            'absent_remarks'   => null,
                            'override_flag'    => 0,
                            'override_remarks' => null,
                            'status'           => 1,
                        ]
                    );
                }
            }

            // Multilocation Logic

            if ($user->multilocation_flag == 1) {

                $locationIds = MultipleLocation::where('user_id', $user->id)
                    ->pluck('location_id');

                $calendarIds = DayStatus::where('location_id', $user->location_id)->pluck('id');

                foreach ($locationIds as $locationId) {

                    foreach ($calendarIds as $calendarId) {

                        AttendanceAbsent::firstOrCreate(
                            [
                                'calendar_id' => $calendarId,
                                'user_id'     => $user->id,
                                'location_id' => $locationId,
                            ],
                            [
                                'absent_flag'      => 1,
                                'absent_remarks'   => null,
                                'override_flag'    => 0,
                                'override_remarks' => null,
                                'status'           => 1,
                            ]
                        );
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Start date updated successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Update Start Date Error', [
                'user_id' => $request->user_id,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Something went wrong. ' . $e->getMessage());
        }
    }
}
