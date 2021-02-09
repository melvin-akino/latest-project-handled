<?php

namespace App\Providers;

use App\Services\LeagueService;
use Illuminate\Support\ServiceProvider;

class LeagueServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('App\Services\LeagueService', function() {
            return new LeagueService;
        });
    }
}
