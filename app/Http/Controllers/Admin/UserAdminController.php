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
use App\Models\CompanyParameter;
use Yajra\DataTables\DataTables;

class UserAdminController extends Controller
{

    use AdminViewSharedDataTrait;

    public function __construct()
    {
        $this->shareAdminViewData();
    }

    // Show the admin management page
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {

            $users = User::leftJoin('day_statuses', 'users.start_calendar_id', '=', 'day_statuses.id')
                ->leftJoin('roles as role_masters', 'users.role_id', '=', 'role_masters.id') // Adjust this based on your actual role relationship
                ->orderBy('users.first_name', 'asc')
                ->select(
                    'users.*',
                    'day_statuses.date as start_date',
                    'role_masters.name as role_name' // Adjust based on your role column name
                );

            if ($user->role == 'Canteen Incharge' || $user->role == 'Canteen Administrator') {
                $users->where('users.location_id', $user->location_id);
            } elseif ($user->role == 'Super Admin') {
            } else {
                return redirect()->back()->with('error', 'You do not have permission to access this page.');
            }

            return DataTables::of($users)
                ->addIndexColumn()

                ->addColumn('full_name', function ($row) {
                    return $row->first_name;
                })

                ->addColumn('role_name', function ($row) {
                    return $row->role_name ?? 'N/A';
                })

                ->addColumn('mobile', function ($row) {
                    return $row->mobile ?? 'N/A';
                })

                ->addColumn('email', function ($row) {
                    return $row->email;
                })

                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge bg-primary">Active</span>';
                    } elseif ($row->status == 0) {
                        return '<span class="badge bg-danger">Inactive</span>';
                    } else {
                        return '<span class="badge bg-warning">Pending</span>';
                    }
                })

                ->addColumn('start_date', function ($row) {

                    if (!empty($row->start_date)) {
                        return \Carbon\Carbon::parse($row->start_date)->format('d-m-Y');
                    }

                    return '
        <button type="button"
            class="btn btn-warning btn-sm update-date-btn"
            data-bs-toggle="modal"
            data-bs-target="#updateDateModal"
            data-id="' . $row->id . '">
            <i class="fa fa-calendar"></i> Start
        </button>
    ';
                })

                ->addColumn('action', function ($row) {

                    $view = route('admin.users.show', $row->id);
                    $edit = route('admin.users.edit', $row->id);
                    $email = route('admin.users.email', $row->id);

                    $buttons = '
        <a href="' . $view . '" class="btn btn-info btn-sm">
            <i class="fa fa-eye"></i>
        </a>
             <a href="' . $edit . '" class="btn btn-warning btn-sm">
            <i class="fa fa-edit"></i>
        </a>
            <a href="' . $email . '" class="btn btn-info btn-sm">
            <i class="fa fa-envelope"></i>
        </a>
  
    ';

                    // Show Suspend button only if Start Date exists
                    if (!empty($row->start_date) && $row->status == 1) {
                        $buttons .= '
            <button type="button"
                class="btn btn-danger btn-sm suspend-user-btn"
                data-bs-toggle="modal"
                data-bs-target="#suspendUserModal"
                    title="Inactive"
                data-id="' . $row->id . '">
                <i class="fa fa-ban"></i>
            </button>
        ';
                    }

                    return $buttons;
                })

                ->rawColumns(['status', 'action', 'start_date'])
                ->make(true);
        }
        return view('admin.users.index');
    }
    public function create()
    {

        $user = Auth::user();

        if ($user->role == 'Super Admin') {
            $locations = Location::orderBy('name')->get();
            $presidentLock = false;
        } elseif ($user->role == 'Canteen Incharge' || $user->role == 'Canteen Administrator') {
            $locations = Location::where('id', $user->location_id)
                ->orderBy('name')
                ->get();
            // Check if a president exists for this location
            $presidentLock = User::where('location_id', $user->location_id)
                ->where('president_flag', 1)
                ->exists();
        } else {
            return redirect()->back()->with('error', 'You are not authorized to access this page.');
        }

        // Fetch and order data for dropdowns
        $roles = RoleMaster::where('status', 1)
            ->orderBy('name', 'asc')
            ->whereIn('name', ['Member', 'Non Member', 'Canteen Incharge', 'Canteen Administrator'])
            ->get();

        $departments = Department::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        $locations = Location::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.users.create', compact('roles', 'departments', 'locations', 'presidentLock'));
    }

    public function store(CreateUserRequest $request)
    {

        if ($request->filled('president_flag') && $request->president_flag == 1) {
            $checkAlreadyPresident = User::where('location_id', $request->location_id)
                ->where('president_flag', 1)
                ->where('status', 1)
                ->first();

            if ($checkAlreadyPresident) {
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Canteen President already assigned for this location. Current President ' . $checkAlreadyPresident->first_name
                    );
            }
        }

        $checkAlreadyExistEmployeeCode = User::where('user_code', $request->user_code)
            ->first();

        if ($checkAlreadyExistEmployeeCode) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Employee ID already exist'
                );
        }



        DB::beginTransaction();

        try {

            if ($request->mobile) {
                $autoGeneratedPassword = $this->generatePasswordFromNameAndMobile(
                    $request->first_name,
                    $request->mobile
                );
            } else {
                $autoGeneratedPassword = $request->username;
            }


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
                'user_code'                 => $request->user_code,
                'last_name'                  => $request->first_name,
                'email'                      => $request->email,
                'mobile'                     => $request->mobile,
                'username'                   => $request->username,
                'designation'                => $request->designation,
                'department_id'              => $request->department_id,
                'location_id'                => $request->location_id,
                'personal_guest_flag'        => $personalGuestFlag,
                'max_personal_guest_allowed' => $maxPersonalGuestAllowed,
                'max_office_guest_allowed'   => $maxOfficeGuestAllowed,

                // Security Details
                'security_amount'            => $request->security_amount ?? 0.00,
                'payment_method'             => $request->payment_method,
                'payment_date'                => $request->payment_date,

                'president_flag'             => $request->president_flag ?? 0,
                'password'                   => Hash::make($autoGeneratedPassword),
                'plain_password'              => $autoGeneratedPassword,
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
            // Mail::to($user->email)->send(
            //     new UserCredentialsMail($user, $plainPassword)
            // );


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

    private function generatePasswordFromNameAndMobile($firstName, $mobile)
    {
        return strtolower(substr($firstName, 0, 3)) . '@' . substr($mobile, -4);
    }

    public function show($id)
    {
        $user = User::leftJoin('day_statuses', 'users.start_calendar_id', '=', 'day_statuses.id')
            ->where('users.id', $id)->select('users.*', 'day_statuses.date as start_date')->first();
        return view('admin.users.show', compact('user'));
    }


    // Update an admin
    // Update an admin
    public function edit($id)
    {
        $user = Auth::user();

        // Check authorization
        if ($user->role == 'Super Admin') {
            $locations = Location::orderBy('name')->get();
            $presidentLock = false;
        } elseif ($user->role == 'Canteen Incharge' || $user->role == 'Canteen Administrator') {
            $locations = Location::where('id', $user->location_id)
                ->orderBy('name')
                ->get();
            $presidentLock = User::where('location_id', $user->location_id)
                ->where('president_flag', 1)
                ->where('id', '!=', $id)
                ->exists();
        } else {
            return redirect()->back()->with('error', 'You are not authorized to access this page.');
        }

        // Find the user to edit
        $userToEdit = User::with(['location', 'department'])->findOrFail($id);

        // Get additional locations from the JSON field
        $otherLocationIds = MultipleLocation::where('user_id', $id)
            ->where('status', 1)
            ->pluck('location_id')
            ->toArray();

        // Fetch dropdown data
        $roles = RoleMaster::where('status', 1)
            ->orderBy('name', 'asc')
            ->whereIn('name', ['Member', 'Non Member', 'Canteen Incharge', 'Canteen Administrator'])
            ->get();

        $departments = Department::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        $allLocations = Location::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.users.edit', compact(
            'userToEdit',
            'roles',
            'departments',
            'allLocations',
            'locations',
            'presidentLock',
            'otherLocationIds'
        ));
    }

    public function update(UpdateUserRequest $request, $id)
    {
        // Find the user
        $user = User::findOrFail($id);

        // Check if president flag is being changed
        if ($request->filled('president_flag') && $request->president_flag == 1) {
            $checkAlreadyPresident = User::where('location_id', $request->location_id)
                ->where('president_flag', 1)
                ->where('status', 1)
                ->where('id', '!=', $id)
                ->first();

            if ($checkAlreadyPresident) {
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Canteen President already assigned for this location. Current President ' . $checkAlreadyPresident->first_name
                    );
            }
        }

        // Check if employee code already exists (excluding current user)
        $checkAlreadyExistEmployeeCode = User::where('user_code', $request->user_code)
            ->where('id', '!=', $id)
            ->first();

        if ($checkAlreadyExistEmployeeCode) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Employee ID already exists'
                );
        }

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

            // Prepare other_location_id as JSON
            $otherLocationIds = $request->filled('other_location_id')
                ? $request->other_location_id
                : [];

            // Remove primary location from other locations if present
            $otherLocationIds = array_filter($otherLocationIds, function ($locationId) use ($request) {
                return $locationId != $request->location_id;
            });

            // Prepare update data
            $updateData = [
                'role_id'                    => $request->role_id,
                'role'                       => $role->name,
                'first_name'                 => $request->first_name,
                'user_code'                  => $request->user_code,
                'email'                      => $request->email,
                'mobile'                     => $request->mobile,
                'designation'                => $request->designation,
                'department_id'              => $request->department_id,
                'location_id'                => $request->location_id,
                'personal_guest_flag'        => $personalGuestFlag,
                'max_personal_guest_allowed' => $maxPersonalGuestAllowed,
                'max_office_guest_allowed'   => $maxOfficeGuestAllowed,
                'security_amount'            => $request->security_amount ?? 0.00,
                'payment_method'             => $request->payment_method,
                'payment_date'               => $request->payment_date,
                'president_flag'             => $request->president_flag ?? 0,
                'status'                     => $request->status,
                'multilocation_flag'         => !empty($otherLocationIds) ? 1 : 0,
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // If you have UserLocation model for additional tracking, update it
            if (class_exists('App\Models\UserLocation')) {
                // First, remove all existing user locations
                UserLocation::where('user_id', $user->id)->delete();

                // Add primary location
                UserLocation::create([
                    'user_id' => $user->id,
                    'location_id' => $request->location_id,
                    'department_id' => $request->department_id,
                    'status' => 1,
                ]);

                // Add additional locations
                foreach ($otherLocationIds as $locationId) {
                    if ($locationId != $request->location_id) {
                        UserLocation::create([
                            'user_id' => $user->id,
                            'location_id' => $locationId,
                            'department_id' => $request->department_id,
                            'status' => 1,
                        ]);
                    }
                }
            }

            // If you have MultipleLocation model, update it
            if (class_exists('App\Models\MultipleLocation')) {
                MultipleLocation::where('user_id', $user->id)->delete();

                foreach ($otherLocationIds as $locationId) {
                    if ($locationId != $request->location_id) {
                        MultipleLocation::create([
                            'user_id' => $user->id,
                            'location_id' => $locationId,
                            'department_id' => $request->department_id,
                            'status' => 1,
                        ]);
                    }
                }
            }

            // Send email notification only if password was changed
            if ($request->filled('password')) {
                $plainPassword = $request->password;
                Mail::to($user->email)->send(
                    new UserCredentialsMail($user, $plainPassword)
                );
            }

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('User Update Error', [
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
                    : 'Something went wrong while updating the user. Please try again.');
        }
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
            } elseif (in_array($user->role, ['Member'])) {

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
            } else {
            }

            // Multilocation Logic
            if (in_array($user->role, ['Non Member', 'Member'])) {
                if ($user->multilocation_flag == 1) {

                    $locationIds = MultipleLocation::where('user_id', $user->id)
                        ->pluck('location_id');

                    $calendarIds = DayStatus::whereIn('location_id', $locationIds)->pluck('id');

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

    public function updateDateBulk(Request $request)
    {
        $request->validate([
            'date'    => 'required|date|after_or_equal:today',
            'user_ids' => 'required|string', // Comma-separated user IDs
        ]);

        // Convert comma-separated string to array
        $userIds = explode(',', $request->user_ids);
        $userIds = array_map('trim', $userIds); // Remove any whitespace
        $userIds = array_filter($userIds); // Remove empty values
        // dd($userIds);

        if (empty($userIds)) {
            return redirect()->back()->with('error', 'No users selected.');
        }

        DB::beginTransaction();

        try {
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedUsers = [];

            // Get all users
            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                // Check if user already has a start date
                if ($user->start_calendar_id) {
                    $skippedCount++;
                    $skippedUsers[] = $user->first_name . ' (ID: ' . $user->id . ')';
                    continue; // Skip this user
                }

                $dayStatus = DayStatus::where('date', $request->date)
                    ->where('location_id', $user->location_id)
                    ->first();

                if (!$dayStatus) {
                    continue; // Skip if date not available
                }

                $user->start_calendar_id = $dayStatus->id;
                $user->save();

                $yearDate = DayStatus::where('id', '>=', $dayStatus->id)
                    ->where('open_flag', 1)
                    ->where('location_id', $user->location_id)
                    ->get();

                if (in_array($user->role, ['Non Member'])) {
                    if ($user->role === 'Non Member') {

                        $dayStatusesOld = DayStatus::where('id', '<', $user->start_calendar_id)
                            ->where('location_id', $user->location_id)
                            ->get();

                        foreach ($dayStatusesOld as $dayStatusesOldData) {

                            AttendanceAbsent::firstOrCreate(
                                [
                                    'calendar_id' => $dayStatusesOldData->id,
                                    'user_id'     => $user->id,
                                    'location_id' => $user->location_id,
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

                        $dayStatuses = DayStatus::where('id', '>=', $user->start_calendar_id)
                            ->where('location_id', $user->location_id)
                            ->get();

                        foreach ($dayStatuses as $dayStatus) {
                            AttendanceAbsent::firstOrCreate(
                                [
                                    'calendar_id' => $dayStatus->id,
                                    'user_id'     => $user->id,
                                    'location_id' => $user->location_id,
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
                } elseif (in_array($user->role, ['Member'])) {

                    $dayStatusesOld = DayStatus::where('id', '<', $user->start_calendar_id)
                        ->where('location_id', $user->location_id)
                        ->get();

                    foreach ($dayStatusesOld as $dayStatusesOldData) {

                        AttendanceAbsent::firstOrCreate(
                            [
                                'calendar_id' => $dayStatusesOldData->id,
                                'user_id'     => $user->id,
                                'location_id' => $user->location_id,
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

                    $dayStatuses = DayStatus::where('id', '>=', $user->start_calendar_id)
                        ->where('location_id', $user->location_id)
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
                                'location_id' => $user->location_id,
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
                } else {
                }

                // Multilocation Logic
                if (in_array($user->role, ['Non Member', 'Member'])) {
                    if ($user->multilocation_flag == 1) {

                        $locationIds = MultipleLocation::where('user_id', $user->id)
                            ->pluck('location_id');

                        $calendarIds = DayStatus::whereIn('location_id', $locationIds)->pluck('id');

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
                }

                $updatedCount++;
            }

            DB::commit();

            // Prepare success/error messages
            $message = "Start date updated successfully for {$updatedCount} users.";

            if ($skippedCount > 0) {
                $message .= " {$skippedCount} user(s) skipped because they already have a start date assigned.";

                if (count($skippedUsers) <= 5) {
                    $message .= " Skipped users: " . implode(', ', $skippedUsers);
                } else {
                    $message .= " Skipped users: " . implode(', ', array_slice($skippedUsers, 0, 5)) . " and " . (count($skippedUsers) - 5) . " more.";
                }
            }

            if ($updatedCount > 0) {
                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'No users were updated. ' . $message);
            }
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Update Start Date Error', [
                'user_ids' => $request->user_ids,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Something went wrong. ' . $e->getMessage());
        }
    }

    public function getSecurityAmount($location)
    {
        $amount = CompanyParameter::where('location_id', $location)->where('status', 1)
            ->value('security_deposit_amount');

        return response()->json([
            'security_amount' => $amount ?? 0
        ]);
    }



    public function suspend(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'suspend_date'     => 'required|date',
            'suspend_remarks'  => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {

            $user = User::findOrFail($request->user_id);

            // Check calendar date exists
            $calendar = DayStatus::whereDate('date', $request->suspend_date)->first();

            if (!$calendar) {
                return back()->withInput()->with(
                    'error',
                    'Selected inactive date is not available in the calendar.'
                );
            }

            // Prevent duplicate suspension
            if (!empty($user->suspend_date)) {
                return back()->with(
                    'error',
                    'This user is already suspended.'
                );
            }

            $user->update([
                'status'               => 0,
                'suspend_date'         => $request->suspend_date,
                'suspend_calendar_id'  => $calendar->id,
                'suspend_remarks'      => $request->suspend_remarks,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User Inactivated successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withInput()->with(
                'error',
                'Something went wrong. ' . $e->getMessage()
            );
        }
    }

    public function email($id)
    {
        $user = User::findOrFail($id);

        if (!$user->email) {
            return redirect()->back()->with('error', 'User does not have an email address.');
        }

        try {
            Mail::to($user->email)->send(
                new UserCredentialsMail($user, $user->plain_password)
            );
            return redirect()->back()->with('success', 'Login credentials email sent successfully to ' . $user->email);
        } catch (TransportExceptionInterface $e) {
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function emailBulk(Request $request)
    {
        $request->validate([
            'user_ids' => 'required'
        ]);

        $userIds = explode(',', $request->user_ids);
        $success = 0;
        $failed = 0;

        foreach ($userIds as $id) {

            $user = User::find($id);

            if (!$user || empty($user->email)) {
                $failed++;
                continue;
            }

            try {
                Mail::to($user->email)->send(
                    new UserCredentialsMail($user, $user->plain_password)
                );

                $success++;
            } catch (TransportExceptionInterface $e) {
                $failed++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        if ($failed == 0) {
            return back()->with(
                'success',
                "{$success} emails sent successfully."
            );
        }

        return back()->with(
            'warning',
            "{$success} emails sent successfully and {$failed} failed."
        );
    }
}
