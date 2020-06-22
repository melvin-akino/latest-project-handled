<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LogToolProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        app()->bind('LogMatrics', function(){  
            return new \App\DebugTool\LogMatrics;
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
