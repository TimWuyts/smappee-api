<?php

use App\Http\Controllers\LocalSmappeeController;
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

Route::controller(SmappeeController::class)
    ->group(function() {
        Route::get('/service-locations', 'getServiceLocations');
        Route::get('/service-location/{id?}', 'getServiceLocation');
        Route::get('/consumption/{id?}', 'getConsumption');
    });

Route::controller(LocalSmappeeController::class)
    ->prefix('local')
    ->name('local.')
    ->group(function() {
        Route::get('/system', 'system');
        Route::get('/sockets', 'listSockets');
        Route::get('/sockets/{key}/{action?}', 'toggleSocket');
    });
