<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PrometheusProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        app()->bind('PrometheusMatric', function(){  
            return new \App\Matrics\PrometheusMatric;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
