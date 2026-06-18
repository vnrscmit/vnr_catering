<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleAdminController extends Controller
{
       // Show the admin management page
    public function index()
    {
        // Get all roles
        $roles = Role::all();
        return view('admin.manage-roles', compact('roles'));
    }

    // Store a new role
    public function store(CreateRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);
    
        try {
            // Send email notification 
            Mail::to($user->email)->send(new NewAccountNotification($user, $user->email));
            $message = ['success' => 'User created successfully. Login details sent to user email.'];
        } catch (TransportExceptionInterface $e) {
  
            $message = [
                'success' => 'User created successfully.',
                'error' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    
        return redirect()->route('admin.users.index')->with($message);
    }
    

    // Update an admin
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
    
        if($user->notice !="change_password_to_activate_account"){

        // Determine ban status and set fields accordingly
        $isBanned = $request->has('ban') && $request->ban === 'on';
        $status = $isBanned ? 0 : 1;
        $notice = $isBanned ? "banned" : null;
        }
        else
        {
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
}
