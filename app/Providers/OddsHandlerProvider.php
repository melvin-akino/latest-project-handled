<?php

namespace App\Providers;

use App\Tasks\TransformKafkaMessageOdds;
use App\Handlers\OddsValidationHandler;
use Illuminate\Support\ServiceProvider;

class OddsHandlerProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('OddsValidationHandler', function () {
            return new OddsValidationHandler();
        });

        $this->app->singleton('TransformKafkaMessageOdds', function () {
            return new TransformKafkaMessageOdds();
        });
    }
}
