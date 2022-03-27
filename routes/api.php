<?php

use App\Http\Controllers\SmappeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(SmappeeController::class)->group(function() {
    Route::get('/measurements', 'measurements');
    Route::get('/measurements/{key}', 'measurement');

    Route::get('/total/load', 'totalLoad');
    Route::get('/total/solar', 'totalSolar');

    Route::get('/sockets', 'sockets');
    Route::get('/sockets/{key}/{action}', 'socket');

    Route::get('/system', 'system');
});
