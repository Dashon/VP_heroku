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

Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('/user', 'Api\UserController');
    Route::get('/user/{user}/paymentSources', 'Api\UserController@paymentSources');
    Route::post('/user/create-setup-intent', 'Api\UserController@createSetupIntent');
    Route::apiResource('/donation', 'Api\DonationController');

    Route::get('/logout', 'Api\Auth\AuthController@logout');
    Route::get('/user', 'Api\Auth\AuthController@user');
});
Route::post('/stripe-web-hook', 'Api\WebHooksController@stripeWebHook');

Route::post('/register', 'Api\Auth\AuthController@register');
Route::post('/login', 'Api\Auth\AuthController@login');

Route::post('/forgot_password', 'Api\Auth\PasswordResetController@create');
Route::get('/forgot_password/find/{token}', 'Api\Auth\PasswordResetController@find');
Route::post('/forgot_password/reset', 'Api\Auth\PasswordResetController@reset');
