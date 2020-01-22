<?php

use Illuminate\Http\Request;

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

/**
 * API V1 Endpoints
 */
Route::group([
    'prefix' => 'v1',
], function () {
    /**
     * User Authentication Routes
     */
    Route::group([
        'prefix' => 'auth',
    ], function () {
        Route::post('login', 'AuthController@login');
        Route::post('register', 'AuthController@register');

        Route::group([
            'middleware' => 'auth:api',
        ], function() {
            Route::get('logout', 'AuthController@logout');

            // ..
        });

        /**
         * Forgot Password and Reset
         */
        Route::group([
            'middleware' => 'api',
            'prefix'     => 'password',
        ], function() {
            Route::post('create', 'AuthController@create');
            Route::get('find/{token}', 'AuthController@find');
            Route::post('reset', 'AuthController@reset');
        });
    });

    /**
     * Authenticated Routes :: User
     */
    Route::group([
        'middleware'    => 'auth:api',
        'prefix'        => 'user',
    ], function () {
        Route::get('/', 'UserController@user');
        Route::get('configuration/odds', 'UserController@sportOddConfigurations');

        Route::post('settings/{type}', 'SettingsController@postSettings');
    });

    /**
     * Resources Routes
     */
    Route::get('timezones', 'ResourceController@getTimezones');
});
