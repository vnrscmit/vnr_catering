<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\PasswordOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $otp = rand(100000, 999999);

        $user = User::where('status', 1)->where('email', $request->email)->first();
        if (!$user) {
            return back()->with('error', 'No active account found with this email address.');
        }

        PasswordResetOtp::updateOrCreate(
            [
                'email' => $request->email,
                'user_id' => $user->id
            ],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(2),
                'is_verified' => 0
            ]
        );

        Mail::to($request->email)->send(new PasswordOtpMail($otp));


        return back()
            ->with('success', 'OTP sent successfully.')
            ->with('showOtpModal', true)
            ->with('email', $request->email);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'otp' => 'required'
        ]);

        $otp = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->where('is_verified', 0)
            ->first();

        if (!$otp) {
            return back()->withErrors([
                'otp' => 'Invalid or expired OTP.'
            ]);
        }

        $otp->update([
            'is_verified' => 1
        ]);

        $encryptedUserId = Crypt::encryptString($otp->user_id);

        return back()
            ->with('showResetModal', true)
            ->with('user_id', $encryptedUserId);
    }



    public function updatePassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $userId = Crypt::decryptString($request->user_id);

        $user = User::findOrFail($userId);

        $user->password = Hash::make($request->password);
         $user->plain_password = $request->password;
        
        $user->save();

        return redirect()->back()
            ->with('success', 'Password updated successfully.');
    }
}
