<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    // Show the login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

 public function apilogin(Request $request)
{
    // Validate request
    $request->validate([
        'mobile' => 'required|string',
        'password' => 'required|string',
    ]);

    try {
        // Find user by mobile number
        $user = User::where('mobile', $request->mobile)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid mobile number or password.',
                'data' => null
            ], 401);
        }

        // Check if user is active
        if ($user->status != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact the administrator.',
                'data' => null
            ], 403);
        }

        // Delete previous tokens
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Login user
        Auth::login($user);

        // Load relationships
        $user->load(['roles', 'department', 'location']);

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',

                'user' => [
                    'id' => $user->id,
                    'name' => $user->first_name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'designation' => $user->designation,
                    'role' => optional($user->roles->first())->name,
                    'role_id' => $user->role_id,
                    'department' => optional($user->department)->name,
                    'department_id' => $user->department_id,
                    'location' => optional($user->location)->name,
                    'location_id' => $user->location_id,
                    'status' => $user->status,
                    'profile_picture' => $user->profile_picture,
                    'token' => $token,
                ],
        
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Login failed: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

    public function get_user_list(Request $request)
    {
        $users = User::with(['roles', 'department', 'location'])->get();

        return response()->json([
            'status' => true,
            'message' => 'User list retrieved successfully.',
            'data' => $users
        ], 200);
    }
}
