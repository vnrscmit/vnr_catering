<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MultipleLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

            // Create new Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Login user
            Auth::login($user);

            // Load relationships
            $user->load(['roles', 'department', 'location']);


            $multiLocationData = MultipleLocation::with('location')
                ->where('user_id', $user->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'location_id'   => $item->location_id,
                        'location_name' => optional($item->location)->name,
                    ];
                })
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'Login successful.',

                'user' => [
                    'id' => $user->id,
                    'name' => explode(' ', trim($user->first_name))[0],
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'designation' => $user->designation,
                    'role' => $user->role,
                    'role_id' => $user->role_id,
                    'department' => optional($user->department)->name,
                    'department_id' => $user->department_id,
                    'location' => optional($user->location)->name,
                    'location_id' => $user->location_id,
                    'status' => $user->status,
                    'profile_picture' => $user->profile_picture,
                    'multilocation_flag' => $user->multilocation_flag,
                    'token' => $token,
                ],
                'multiLocationData' => $multiLocationData,

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

    public function getUserProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::select(
            'id',
            'first_name as name',
            'designation',
            'role',
            'email',
            'mobile',
            'profile_picture as photo',
            'department_id',
            'location_id'
        )
            ->with([
                'department:id,name',
                'location:id,name'
            ])
            ->where('id', $request->user_id)
            ->where('status', 1)
            ->first();

        if ($user) {
            $user->department_name = optional($user->department)->name;
            $user->location_name = optional($user->location)->name;

            unset($user->department);
            unset($user->location);
        }

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
                'data' => null,
            ], 404);
        }

        if ($user) {
            if (!empty($user->photo)) {
                $user->photo = asset('storage/profile-picture/' . $user->photo);
            } else {
                $user->photo = asset('storage/profile-picture/default.png');
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'User profile fetched successfully.',
            'data' => $user,
        ], 200);
    }
}
