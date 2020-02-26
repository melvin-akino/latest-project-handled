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

/*
|--------------------------------------------------------------------------
| API V1 Endpoints
|--------------------------------------------------------------------------
|
| Multiline v2.0 API Endpoints
| Registered route endpoints that are initially used by
| the WEB application.
|
| Some API Endpoints registered below can be re-used upon
| development of MOBILE application.
|
*/
Route::group([
    'prefix' => 'v1',
], function () {
    /** User Authentication Route Endpoints*/
    Route::group([
        'prefix' => 'auth',
    ], function () {
        Route::post('login', 'AuthController@login');
        Route::post('register', 'AuthController@register');
        Route::middleware('auth:api')->post('logout', 'AuthController@logout');

        /** Forgot Password and Reset Route Endpoints */
        Route::group([
            'middleware' => 'api',
            'prefix'     => 'password',
        ], function() {
            Route::post('create', 'AuthController@create');
            Route::get('find/{token}', 'AuthController@find');
            Route::post('reset', 'AuthController@reset');
        });
    });

    /** Authenticated Routes :: User */
    Route::group([
        'middleware'    => 'auth:api',
        'prefix'        => 'user',
    ], function () {
        Route::get('/', 'UserController@user');

        /** Authenticated User Settings Management Route Endpoints */
        Route::post('settings/{type}', 'SettingsController@postSettings')
            ->where('type', '^(general|trade-page|bet-slip|notifications-and-sounds|language|bookies|bet-columns|profile|change-password|reset)$');
        Route::post('settings/{type}/{sportId}', 'SettingsController@postSettings')
            ->where('type', '^(bet-columns)$');
        Route::get('settings/{type}', 'SettingsController@getSettings')
            ->where('type', '^(general|trade-page|bet-slip|notifications-and-sounds|language|bookies|bet-columns)$');

        /** Authenticated User Wallet Management Route Endpoints */
        Route::get('wallet', 'WalletController@userWallet');
    });

    /** Resources Route Endpoints */
    Route::get('timezones', 'ResourceController@getTimezones');

    Route::group([
        'middleware' => 'auth:api',
        'prefix'     => 'sports'
    ], function () {
        Route::get('/', 'SportController@getSports');
        Route::get('odds', 'SportController@configurationOdds');
    });

    Route::middleware('auth:api')->get('bookies', 'ResourceController@getProviders');

    /** Game Data Route Endpoints*/
    Route::group([
        'middleware' => 'auth:api',
        'prefix'     => 'trade',
    ], function () {
        /** User Bet bar Management Route Endpoints */
        Route::get('betbar', 'TradeController@getUserBetbar');

        /** User Watchlist Management Route Endpoints */
        Route::post('watchlist/{action}', 'TradeController@postManageWatchlist')->where('action', '^(add|remove)$');

        /** League List Route Endpoints for Initial Page Load */
        Route::prefix('leagues')->group(function () {
            Route::get('/', 'TradeController@getInitialLeagues');
            Route::post('toggle', 'TradeController@postManageSidebarLeagues');
        });
    });
});

Route::fallback(function () {
    return response()->json([
        'status'      => true,
        'status_code' => 404,
        'message'     => trans('generic.not-found'),
    ], 404);
});
