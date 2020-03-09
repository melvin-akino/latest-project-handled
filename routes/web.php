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
    });
});

Route::get('/{any}', 'AppController@index')->where('any', '^(?!api).*');