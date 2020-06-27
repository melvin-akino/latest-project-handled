<?php

namespace App\Providers;

use App\Handlers\SwooleHandler;
use Illuminate\Support\ServiceProvider;

class SwooleServiceProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('swoolehandler', function ($app) {
            return new SwooleHandler($app->make('swoole'));
        });
    }
}
