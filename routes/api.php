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
    Route::get('/getUserProfile', [UserController::class, 'getUserProfile']);
    Route::get('/menu-list', [ApiMenuController::class, 'menuList']);
    Route::get('/dashboard', [ApiDashboardController::class, 'dashboard']);
    Route::get('/calendar', [ApiAttendanceController::class, 'calendar']);
    Route::post('/add-guest', [ApiAttendanceController::class, 'guestCreate']);
    Route::get('/guest-list-today', [ApiAttendanceController::class, 'guestListToday']);
    Route::get('/guest-list', [ApiAttendanceController::class, 'guestList']);
    Route::post('/mark-attendance', [ApiAttendanceController::class, 'markAttendance']);
    Route::get('/getDepartment', [ApiAttendanceController::class, 'getDepartment']);
    Route::get('/getUsersByDepartment', [ApiAttendanceController::class, 'getuserByDepartment']);

    Route::get('/menu-list-datewise', [ApiMenuController::class, 'menuListDateWise']);
    Route::get('/today-menu-list', [ApiMenuController::class, 'menuListToday']);
    Route::post('/store-update-menu', [ApiMenuController::class, 'storeOrUpdateMenu']);

    //Canteen Incharge 
    Route::get('/manage-attendance', [ApiAttendanceController::class, 'manageAttendance']);
    Route::post('/manage-override-attendance', [ApiAttendanceController::class, 'overrideAttendance']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
});

Route::post('/generate-year', [ApiAttendanceController::class, 'generateYear']);
Route::post('login', [UserController::class, 'apilogin'])->name('user.login');
