<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
Route::get('user-list', [UserController::class, 'get_user_list'])->name('user.list');
});

Route::post('login', [UserController::class, 'apilogin'])->name('user.login');


