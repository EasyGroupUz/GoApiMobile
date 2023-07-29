<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderDetailsController;
use App\Http\Controllers\CarsController;
use App\Http\Controllers\CommentScoreController;
use App\Http\Controllers\ComplainController;
use App\Http\Controllers\CountryController;


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

    Route::group(['prefix' => 'orderDetail'], function () {
        Route::post('/store', [OrderDetailsController::class, 'store']);
        Route::get('/find-by-order-search', [OrderDetailsController::class, 'searchClients']);
        // Route::get('/show', [OrderController::class, 'orderShow']);
    });
    
    Route::group(['prefix' => 'car'], function () {
        Route::get('/driver-car',[CarsController::class, 'myTaxi']);
        Route::get('/list', [CarsController::class, 'information']);
        Route::post('/create', [CarsController::class, 'create']);
        Route::post('/card-list', [CarsController::class, 'cardList']);
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('/show', [UserController::class, 'show']);
        Route::post('/update', [UserController::class, 'update']);
        Route::post('/delete', [UserController::class, 'delete']);
    });

    Route::group(['prefix' => 'order'], function () {
        Route::get('/history', [OrderController::class, 'history']);
        Route::get('/index', [OrderController::class, 'index']);
        Route::get('/show', [OrderController::class, 'show']);
        Route::post('/create', [OrderController::class, 'create']);
        Route::get('/expired', [OrderController::class, 'expired']);
        Route::get('/find-by-order-search', [OrderController::class, 'searchTaxi']);
        Route::post('/booking', [OrderController::class, 'booking']);

    });

    Route::group(['prefix' => 'country'], function () {
        Route::get('/index', [CountryController::class, 'index']);
    });

    Route::group(['prefix' => 'comment'], function () {
        Route::post('/create',[CommentScoreController::class, 'commentCreate']);
        Route::get('/get-comments',[CommentScoreController::class, 'getComments']);
    });
    
    Route::group(['prefix' => 'complain'], function () {
        Route::post('/create', [ComplainController::class, 'create']);
    });
});
