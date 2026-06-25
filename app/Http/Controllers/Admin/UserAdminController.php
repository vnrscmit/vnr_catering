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
use App\Models\Department;
use App\Models\Location;
use App\Models\RoleMaster;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use App\Models\UserLocation;

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
        $users = User::where('id', '!=', Auth::id())->get();
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
       
        try {

            $password = $request->password;
            $role = RoleMaster::find($request->role_id);

            $user = User::create([
                'role_id'       => $request->role_id,
                'role'          => $role->name,
                'first_name'    => $request->first_name,
                'last_name'     => $request->first_name,
                'email'         => $request->email,
                'mobile'        => $request->mobile,
                'designation'   => $request->designation,
                'department_id' => $request->department_id,
                'location_id'   => $request->location_id,
                'password'      => Hash::make($password),
                'status'        => $request->status,
                'notice'        => 'Account created successfully',
                'activation_token' => Str::random(60),
            ]);

            // Base Location
            UserLocation::firstOrCreate([
                'user_id'       => $user->id,
                'location_id'   => $request->location_id,
                'department_id' => $request->department_id,
            ], [
                'status' => 1,
            ]);

            // Other Locations
            if (!empty($request->other_location_id)) {

                foreach ($request->other_location_id as $locationId) {
                    UserLocation::firstOrCreate([
                        'user_id'       => $user->id,
                        'location_id'   => $locationId,
                        'department_id' => $request->department_id,
                    ], [
                        'status' => 1,
                    ]);
                }
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
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
}
