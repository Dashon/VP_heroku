<?php
Route::group([
    'namespace' => 'Api\Admin',
    'middleware' => 'api',
    'prefix' => 'admin'
], function () {
    Route::group([
        'middleware' => 'auth:api'
    ], function () {
        Route::apiResource('user', 'UserController');
        Route::apiResource('donation', 'DonationController');
        Route::apiResource('transaction', 'TransactionController');

        Route::get('user/{user}/donations', 'UserController@userDonations');

    });
});
