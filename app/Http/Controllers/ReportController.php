<?php

namespace App\Http\Controllers;

use App\Models\MultipleLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function dailyAttendance()
    {
        $authUser = Auth::user();

        if (in_array($authUser->role, ['Member', 'Non Member'])) {
            $user = User::whereIn('id', $authUser->id)->get();
        } else if (in_array($authUser->role, ['Canteen Administrator'])) {
            $singleLinkedUserIds = User::where('location_id', $authUser->location_id)
                ->whereNotNull('start_calendar_id')
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->where('status', 1)
                ->pluck('id');

            $multiLinkedUserIds = MultipleLocation::join('users', 'multiple_locations.user_id', '=', 'users.id')
                ->where('multiple_locations.location_id', $authUser->location_id)
                ->where('users.status', 1)
                ->whereNotIn('users.role', ['Admin', 'Super Admin', 'Canteen Incharge', 'Canteen Administrator'])
                ->pluck('multiple_locations.user_id');

            $allLinkedUserIds = $singleLinkedUserIds
                ->merge($multiLinkedUserIds)
                ->unique()
                ->values();

            $user = User::whereIn('id', $allLinkedUserIds)->get();
        } else {
            return redirect()->back()->with('error', 'You do not have permission for repots.');
        }

        
    }
}
