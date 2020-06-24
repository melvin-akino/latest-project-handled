<?php

namespace App\Providers;

use App\Tasks\{TransformKafkaMessageEventData,
    TransformKafkaMessageOdds,
    TransformKafkaMessageOddsSaveToDb,
    UpdateMatchedEventData
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

        $this->app->singleton('TransformKafkaMessageEventData', function () {
            return new TransformKafkaMessageEventData();
        });

        $this->app->singleton('TransformKafkaMessageOdds', function () {
            return new TransformKafkaMessageOdds();
        });

        $this->app->singleton('UpdateMatchedEventData', function () {
            return new UpdateMatchedEventData();
        });

        $this->app->singleton('TransformKafkaMessageOddsSaveToDb', function () {
            return new TransformKafkaMessageOddsSaveToDb();
        });

        $this->app->singleton('OddsSaveToDbHandler', function () {
            return new OddsSaveToDbHandler();
        });
    }
}