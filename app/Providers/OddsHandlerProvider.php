<?php

namespace App\Providers;

use App\Tasks\{
    TransformKafkaMessageOdds,
    TransformKafkaMessageOddsSaveToDb
};
use App\Handlers\{OddsSaveToDbHandler, OddsTransformationHandler, OddsValidationHandler};
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

        $this->app->singleton('OddsTransformationHandler', function () {
            return new OddsTransformationHandler();
        });

        $this->app->singleton('TransformKafkaMessageOdds', function () {
            return new TransformKafkaMessageOdds();
        });
    }
}
