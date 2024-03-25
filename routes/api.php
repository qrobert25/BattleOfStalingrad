<?php

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

Route::get('/v1/tanks/{id}', 'App\Http\Controllers\TanksController@getTank');
Route::get('/v1/map/{id}', 'App\Http\Controllers\MapsController@getMap');
Route::get('/v1/simulate/', 'App\Http\Controllers\GameSessionController@createGameSession');
