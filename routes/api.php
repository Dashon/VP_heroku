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

Route::group([
    'namespace' => 'Api',
    'middleware' => 'api'
], function () {
    Route::post('stripe-web-hook', 'WebHooksController@stripeWebHook');

    Route::group([
        'middleware' => 'auth:api'
    ], function () {
        Route::apiResource('user', 'UserController');
        Route::apiResource('donation', 'DonationController');

        Route::get('payment-sources', 'PaymentSourcesController@index');
        Route::get('payment-sources/{stripe_id}', 'PaymentSourcesController@showPaymentSource');
        Route::post('payment-sources/create-setup-intent', 'PaymentSourcesController@createSetupIntent');
        Route::post('payment-sources/add-ank-account', 'PaymentSourcesController@storeBankAccount');
        Route::delete('payment-sources/{stripe_id}', 'PaymentSourcesController@destroyPaymentSource');
    });
});


require __DIR__ . '/auth/auth.php';
require __DIR__ . '/admin/admin.php';
