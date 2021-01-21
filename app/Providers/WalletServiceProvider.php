<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WalletService;

class WalletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('App\Services\WalletService', function() {
          return new WalletService(config('wallet.url'), config('wallet.client_id'), config('wallet.client_secret'));
        });
    }
}
