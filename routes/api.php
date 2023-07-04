<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;


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



Route::post('/login', [AuthController::class, 'Login']);
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::group(['prefix' => 'order'], function () {
        Route::get('/search/taxi', [OrderController::class, 'searchTaxi']);
        Route::get('/show', [OrderController::class, 'orderShow']);
    });
    Route::post('/logout', [AuthController::class, 'Logout']);
});
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
