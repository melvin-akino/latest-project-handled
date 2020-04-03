<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::namespace('CRM')->prefix('admin')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::get('/', 'LoginController@index')->name('crm');
        Route::post('login', 'LoginController@login')->name('crm.login');
        Route::get('logout', 'LoginController@logout')->name('crm.logout');
    });

    Route::middleware('auth:crm')->group(function () {
        Route::get('dashboard', 'DashboardController@index')->name('dashboard');
         /* Wallet route */
    	Route::namespace('Wallet')->prefix('wallet')->group(function () {
			Route::prefix('exchange_rates')->group(function () {
				Route::get('datatable', 'ExchangeRateController@dataTable')->name('wallet.exchange_rates.dataTable');
			});

			Route::resource('exchange_rates', 'ExchangeRateController', ['only' => [
				'index', 'store', 'update'
			]]);

			Route::prefix('currencies')->group(function () {
				Route::get('datatable', 'CurrencyController@dataTable')->name('wallet.currencies.dataTable');
			});

			Route::resource('currencies', 'CurrencyController', ['only' => [
				'index', 'store', 'update'
			]]);
			  /* Transfer route */
    		Route::prefix('transfer')->group(function () {
				Route::get('/', 'TransferController@index')->name('wallet.transfer.index');
				Route::post('/', 'TransferController@transfer')->name('wallet.transfer.transfer');
				Route::get('datatable', 'TransferController@dataTable')->name('wallet.transfer.dataTable');
			});
   	 		/* end transfer route */		
		});
    /* end wallet route */

    });
});

Route::get('/{any}', 'AppController@index')->where('any', '^(?!api).*');