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
$ Route::get('/prometheus_GGT8', 'PrometheusController@index');

Route::namespace('CRM')->prefix('admin')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::get('/', 'LoginController@index')->name('crm');
        Route::post('login', 'LoginController@login')->name('crm.login');
        Route::get('logout', 'LoginController@logout')->name('crm.logout');
    });

    Route::middleware('auth:crm')->group(function () {
        Route::get('dashboard', 'DashboardController@index')->name('dashboard');

        /*Providers related routes*/
        Route::get('providers', 'ProvidersController@index')->name('providers');
        Route::get('providers/list', 'ProvidersController@list')->name('providers.list');
        Route::post('providers/manage', 'ProvidersController@manage')->name('providers.manage');
        Route::post('providers/sort','ProvidersController@prioritize')->name('providers.sort');
        Route::get('provider_accounts/{id}', 'ProviderAccountsController@index')->name('provider.accounts');
        Route::get('provider_accounts/delete/{id}', 'ProviderAccountsController@softDelete')->name('provider_accounts.softdelete');
        Route::post('provider_accounts/manage', 'ProviderAccountsController@manage')->name('provider_accounts.manage');
        
        /*System Configurations Routes*/
        Route::get('system_configurations', 'SystemConfigurationsController@index')->name('system_configurations');
        Route::get('system_configurations/all', 'SystemConfigurationsController@all')->name('system_configurations.all');
        Route::post('system_configurations/manage', 'SystemConfigurationsController@manage')->name('system_configurations.manage');
        Route::get('system_configurations/list', 'SystemConfigurationsController@list')->name('system_configurations.list');

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
	    /* Account Route */
	    Route::namespace('Accounts')->group(function() {
			
			Route::prefix('accounts')->group(function() {
				Route::get('/', 'AccountsController@index')->name('accounts.index');
				Route::get('datatable', 'AccountsController@dataTable')->name('accounts.dataTable');
				Route::post('change_password', 'AccountsController@changePassword')->name('accounts.change_pwd');
				Route::get('{account}', 'AccountsController@details')->name('accounts.details');
				Route::put('{account}', 'AccountsController@update')->name('accounts.update');
				Route::get('ledger/{ledger}/source-info', 'WalletController@ledgerSourceInfo')->name('userwallet.ledgerSourceInfo');
				Route::get('wallet/datatable/{wallet}', 'WalletController@dataTable')->name('userwallet.dataTable');

			});
		
		});
	    /* end Account Route */
    });
});

Route::get('/{any}', 'AppController@index')->where('any', '^(?!api).*');