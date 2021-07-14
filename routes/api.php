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
#Route::group(['middleware' => ['prometheuslog']], function () {

    Route::group([
        'prefix' => 'v1',
    ], function () {
        /** User Authentication Route Endpoints*/
        Route::group([
            'prefix' => 'auth',
        ], function () {
            Route::post('login', 'AuthController@login')->middleware(['prometheuslog']);
//            Route::post('register', 'AuthController@register');
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

        /** Resources Route Endpoints */
        Route::get('timezones', 'ResourceController@getTimezones');

        /** Authenticated Routes :: User */
        Route::group(['middleware' => \App\Http\Middleware\StillActive::class], function() {
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

            Route::group([
                'middleware' => 'auth:api',
                'prefix'     => 'sports'
            ], function () {
                Route::get('/', 'SportController@getSports');
                Route::get('odds', 'SportController@configurationOdds');
            });

            Route::middleware('auth:api')->get('bookies', 'ResourceController@getProviders');

            Route::middleware('auth:api')->group(function () {
                /** Orders Route Endpoints */
                Route::prefix('orders')->group(function () {
                    Route::get('all', 'OrdersController@myOrders');
                    Route::get('myOrdersV2', 'OrdersController@myOrdersV2');
                    Route::get('myHistory', 'OrdersController@myHistory');
                    Route::get('bet-matrix', 'OrdersController@betMatrixOrders');
                    Route::get('/{memUID}', 'OrdersController@getEventMarketsDetails');
                    Route::get('logs/{uid}', 'OrdersController@getBetSlipLogs');

                    Route::post('bet', 'OrdersController@postPlaceBet')->middleware(['prometheusopenbet','prometheusurlog']);
                    Route::post('minmaxlog', 'OrdersController@receiveMinMaxLog');

                    Route::get('bet/event-details', 'OrdersController@getEventDetails');
                    Route::post('bet/retry', 'OrdersController@postRetryBet');
                });

                /** Game Data Route Endpoints*/
                Route::prefix('trade')->group(function () {
                    /** User Bet bar Management Route Endpoints */
                    Route::get('betbar', 'TradeController@getUserBetbar');

                    /** User Watchlist Management Route Endpoints */
                    Route::post('watchlist/{action}', 'TradeController@postManageWatchlist')->where('action', '^(add|remove)$');

                    /** League List Route Endpoints for Initial Page Load */
                    Route::prefix('leagues')->group(function () {
                        Route::get('/', 'TradeController@getInitialLeagues');
                        Route::post('toggle/{action}', 'TradeController@postManageSidebarLeagues');
                    });

                    Route::get('events', 'TradeController@getUserEvents');
                    Route::get('other-markets/{meUID}', 'TradeController@getEventOtherMarkets');
                    /** Search Suggestions Route Endpoint */
                    Route::post('search', 'TradeController@postSearchSuggestions');
                });

                /** Leagues Endpoints*/
                Route::prefix('leagues')->group(function () {
                    Route::get('list', 'LeaguesController@list');
                });

                /** Teams Endpoints*/
                Route::prefix('teams')->group(function () {
                    Route::get('list', 'TeamsController@list');
                });
            });
        });
    });
#});


Route::fallback(function () {
    return response()->json([
        'status'      => true,
        'status_code' => 404,
        'message'     => trans('generic.not-found'),
    ], 404);
});
