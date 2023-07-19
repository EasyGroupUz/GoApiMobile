<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailsController;



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


//
Route::post('/login', [AuthController::class, 'Login'])->name('loginPhone');
Route::post('/verify', [AuthController::class, 'loginToken'])->name('loginToken');
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/verify-get', [AuthController::class, 'loginToken_get']);
    Route::post('/logout', [AuthController::class, 'Logout']);
    Route::post('/set-name-surname', [AuthController::class, 'Set_name_surname']);

    // Route::group(['prefix' => 'client'], function () {
        Route::group(['prefix' => 'orderDetail'], function () {
            Route::post('/store', [OrderDetailsController::class, 'store']);
            // Route::get('/show', [OrderController::class, 'orderShow']);
        });
    // });

    Route::group(['prefix' => 'order'], function () {
        Route::get('/history', [OrderController::class, 'history']);
        Route::get('/index', [OrderController::class, 'index']);
        Route::get('/show', [OrderController::class, 'show']);
        Route::post('/create', [OrderController::class, 'create']);
        Route::get('/expired', [OrderController::class, 'expired']);
    });
});
