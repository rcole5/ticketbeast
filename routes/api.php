<?php

use App\Http\Controllers\ConcertOrdersController;
use App\Http\Controllers\ConcertsController;
use Illuminate\Http\Request;
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

//Route::get('/concerts/{id}', [ConcertsController::class, 'show']);
Route::apiResource('concerts', ConcertsController::class);
Route::apiResource('concerts.orders', ConcertOrdersController::class);
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
