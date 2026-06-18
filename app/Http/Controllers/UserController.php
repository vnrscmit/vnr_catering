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
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        try {
            // Find user by email
            $user = User::where('email', $request->email)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email or password.',
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

            // Revoke all previous tokens (optional - for security)
            $user->tokens()->delete();

            // Create new Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;


           

            // Login the user (optional - for session)
              Auth::login($user);
             $user = User::with(['roles', 'department', 'location'])->find($user->id);
            // Return success response
            return response()->json([
                'status' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                        'designation' => $user->designation,
                        'role' =>$user->roles->name,
                        'role_id' => $user->role_id,
                        'department' =>  $user->department->name,
                        'department_id' => $user->department_id,
                        'location' =>$user->location->name,
                        'location_id' => $user->location_id,
                        'status' => $user->status,
                        'profile_picture' => $user->profile_picture,
                        'created_at' => $user->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
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
