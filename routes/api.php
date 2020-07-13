<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/translate/analyze', 'TranslateController@analyze')->name('TranslateAnalyze');
Route::post('/blacklist', 'TranslateController@insertWordToBlackList')->name('BlacklistAdd');

Route::get('/generate/{translationGenerate}', 'TranslateController@generateGet')->name('GenerateGet');
Route::post('/generate', 'TranslateController@generate')->name('GenerateCreate');

