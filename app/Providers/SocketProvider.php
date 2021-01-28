<?php

namespace App\Providers;

use App\Tasks\SocketDataPush;
use Illuminate\Support\ServiceProvider;

class SocketProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('SocketDataPush', function () {
            return new SocketDataPush();
        });
    }
}
