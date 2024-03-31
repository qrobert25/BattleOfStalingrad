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

Route::post('/v1/scores/', 'App\Http\Controllers\ScoresController@saveScore');
Route::get('/v1/scores/{id}', 'App\Http\Controllers\ScoresController@getScore');

Route::get('/v1/leaderboard/{period}', 'App\Http\Controllers\ScoresController@getLeaderboard');
Route::get('/v1/leaderboard', 'App\Http\Controllers\ScoresController@getLeaderboard');

