<?php

use App\Http\Controllers\API\ApiMenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\API\ApiAttendanceController;
use App\Http\Controllers\API\ApiDashboardController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('user-list', [UserController::class, 'get_user_list'])->name('user.list');
    Route::get('/menu-list', [ApiMenuController::class, 'menuList']);
    Route::get('/dashboard', [ApiDashboardController::class, 'dashboard']);
    Route::get('/calendar', [ApiAttendanceController::class, 'calendar']);
    Route::post('/add-guest', [ApiAttendanceController::class, 'guestCreate']);
    Route::post('/mark-attendance', [ApiAttendanceController::class, 'markAttendance']);

        Route::get('/today-menu-list', [ApiMenuController::class, 'menuListToday']);
});

Route::post('/generate-year', [ApiAttendanceController::class, 'generateYear']);
Route::post('login', [UserController::class, 'apilogin'])->name('user.login');
