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
            Route::post('logout', 'AuthController@logout');

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

        Route::post('settings/{type}', 'SettingsController@postSettings')
            ->where('type', '^(general|trade-page|bet-slip|notifications-and-sounds|language|bookies|bet-columns|profile|change-password|reset)$');
        Route::post('settings/{type}/{sportId}', 'SettingsController@postSettings')
            ->where('type', '^(bet-columns)$');
        Route::get('settings/{type}', 'SettingsController@getSettings')
            ->where('type', '^(general|trade-page|bet-slip|notifications-and-sounds|language|bookies|bet-columns)$');

        Route::get('wallet', 'WalletController@userWallet');
    });

    /**
     * Resources Routes
     */
    Route::get('timezones', 'ResourceController@getTimezones');
    Route::get('sports/odds', 'SportController@configurationOdds');
    Route::middleware('auth:api')->get('bookies', 'ResourceController@getProviders');

    /**
     * Game Data Routes
     */
});

Route::fallback(function () {
    return response()->json([
        'status'      => true,
        'status_code' => 404,
        'message'     => trans('generic.not-found'),
    ], 404);
});
