<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('user-sign-up', [LoginController::class, 'signUp']);
Route::post('user-sign-in', [LoginController::class, 'signIn']);
Route::post('user-sign-out', [LoginController::class, 'logout']);


Route::middleware(['auth:sanctum'])->group(function () {

    //user
    Route::get('/user-profile', [UserController::class, 'profile']);



});

Route::post('admin-sign-up', [LoginController::class, 'signUpAsAdmin']);
Route::post('admin-sign-in', [LoginController::class, 'signInAsAdmin']);

Route::middleware(['auth:sanctum'])->group(function () {

    //user: admin
    Route::get('/admin-profile', [AdminController::class, 'profile']);

    //food management
    Route::post('/food-create', [AdminController::class, 'create']);
    Route::put('/food-edit/{id}', [AdminController::class, 'update']);
    Route::post('/food-view', [AdminController::class, 'view']);
    Route::delete('/food-remove/{id}', [AdminController::class, 'destroy']);


});
