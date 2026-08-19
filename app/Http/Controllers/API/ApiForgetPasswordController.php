<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\PasswordOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ApiForgetPasswordController extends Controller
{
    /**
     * Send OTP
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->where('status', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'No active account found with this email address.',
            ], 404);
        }

        $otp = rand(100000, 999999);

        PasswordResetOtp::updateOrCreate(
            [
                'email'   => $user->email,
                'user_id' => $user->id,
            ],
            [
                'otp'         => $otp,
                'expires_at'  => Carbon::now()->addMinutes(2),
                'is_verified' => 0,
            ]
        );

        Mail::to($user->email)->send(new PasswordOtpMail($otp));

        return response()->json([
            'status'  => true,
            'message' => 'OTP sent successfully.',
        ], 200);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->where('is_verified', 0)
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
            ], 400);
        }

        $otp->update([
            'is_verified' => 1,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'OTP verified successfully.',
            'user_id' => Crypt::encryptString($otp->user_id),
        ], 200);
    }

    /**
     * Update Password
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $userId = Crypt::decryptString($request->user_id);

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $user->password = Hash::make($request->password);
             $user->plain_password = $request->new_password;
            $user->save();

            PasswordResetOtp::where('user_id', $userId)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully.',
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid user.',
            ], 400);
        }
    }
}