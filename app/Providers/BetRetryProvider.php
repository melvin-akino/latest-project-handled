<?php

namespace App\Providers;

use App\Tasks\BetRetryPush;
use Illuminate\Support\ServiceProvider;

class BetRetryProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('BetRetryPush', function () {
            return new BetRetryPush();
        });
    }
}
