<?php
Route::group([
    'namespace' => 'Api\Auth',
    'middleware' => 'api',
], function () {
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');

    Route::get('forgot_password/{token}', 'PasswordResetController@find');
    Route::post('forgot_password', 'PasswordResetController@create');
    Route::post('forgot_password/reset', 'PasswordResetController@reset');

    Route::group([
        'middleware' => 'auth:api'
    ], function () {
        Route::get('logout', 'AuthController@logout');
    });
});
