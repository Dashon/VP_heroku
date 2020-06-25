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
Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('/user', 'Api\UserController');
    Route::get('/user/{user}/paymentSources', 'Api\UserController@paymentSources');
    Route::post('/user/create-setup-intent', 'Api\UserController@createSetupIntent');
    Route::post('change_password', 'Api\AuthController@change_password');
    Route::apiResource('/donation', 'Api\DonationController');
});
Route::post('/register', 'Api\AuthController@register');
Route::post('/login', 'Api\AuthController@login');
Route::post('/forgot_password', 'Api\AuthController@forgot_password');
Route::post('/stripe-web-hook', 'Api\WebHooksController@stripeWebHook');
